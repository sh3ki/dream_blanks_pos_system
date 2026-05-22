<?php

namespace App\Models;

class Invoice extends Model
{
    protected static string $table = 'invoices';
    protected static bool $softDelete = true;

    public static function generateNumber(): string
    {
        $prefix  = static::db()->selectOne("SELECT setting_value FROM settings WHERE setting_key = 'invoice_prefix'")['setting_value'] ?? 'INV-';
        $lastRow = static::db()->selectOne(
            "SELECT invoice_number FROM invoices WHERE invoice_number LIKE ? ORDER BY id DESC LIMIT 1",
            ["{$prefix}%"]
        );

        if ($lastRow) {
            // Extract only the trailing numeric sequence (after the prefix)
            $suffix = ltrim(substr($lastRow['invoice_number'], strlen($prefix)), '0') ?: '0';
            $next   = (int)$suffix + 1;
        } else {
            $next = 1;
        }

        return $prefix . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    public static function findWithDetails(int $id): ?array
    {
        $invoice = static::db()->selectOne(
            "SELECT i.*, c.full_name as client_name,
                    c.email as client_email,
                    (SELECT cc.contact_number FROM client_contacts cc
                     WHERE cc.client_id = i.client_id AND cc.is_primary = 1
                     LIMIT 1) as client_phone,
                    (SELECT CONCAT_WS(', ',
                         ca.street_address,
                         NULLIF(ca.barangay,''),
                         ca.city,
                         NULLIF(ca.province,''),
                         NULLIF(ca.postal_code,''))
                     FROM client_addresses ca
                     WHERE ca.client_id = i.client_id AND ca.is_primary = 1
                       AND ca.deleted_at IS NULL
                     LIMIT 1) as client_address,
                    CONCAT(u.first_name,' ',u.last_name) as created_by_name
             FROM invoices i
             LEFT JOIN clients c ON c.id = i.client_id
             INNER JOIN users u ON u.id = i.created_by
             WHERE i.id = ? AND i.deleted_at IS NULL",
            [$id]
        );

        if ($invoice) {
            $invoice['items']    = static::getItems($id);
            $invoice['payments'] = static::getPayments($id);
        }
        return $invoice;
    }

    public static function getItems(int $invoiceId): array
    {
        return static::db()->select(
            "SELECT ii.*, p.name as product_name, p.sku
             FROM invoice_items ii
             INNER JOIN products p ON p.id = ii.product_id
             WHERE ii.invoice_id = ?",
            [$invoiceId]
        );
    }

    public static function getPayments(int $invoiceId): array
    {
        return static::db()->select(
            "SELECT pay.*, CONCAT(u.first_name,' ',u.last_name) as recorded_by_name,
                    CONCAT(cu.first_name,' ',cu.last_name) as confirmed_by_name
             FROM payments pay
             INNER JOIN users u ON u.id = pay.recorded_by
             LEFT JOIN users cu ON cu.id = pay.confirmed_by
             WHERE pay.invoice_id = ?
             ORDER BY pay.payment_date ASC",
            [$invoiceId]
        );
    }

    public static function search(array $filters, int $page, int $perPage): array
    {
        $where  = "i.deleted_at IS NULL";
        $params = [];

        if (!empty($filters['search'])) {
            $term    = "%{$filters['search']}%";
            $where  .= " AND (i.invoice_number LIKE ? OR c.full_name LIKE ?)";
            $params  = array_merge($params, [$term, $term]);
        }

        if (!empty($filters['status'])) {
            $where   .= " AND i.payment_status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['date_from'])) {
            $where   .= " AND DATE(i.invoice_date) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where   .= " AND DATE(i.invoice_date) <= ?";
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['method'])) {
            $where   .= " AND i.primary_payment_mode = ?";
            $params[] = $filters['method'];
        }

        if (!empty($filters['invoice_sent'])) {
            $where   .= " AND i.invoice_sent = ?";
            $params[] = $filters['invoice_sent'];
        }

        $allowedSort = ['i.invoice_number','i.invoice_date','i.total_amount','i.payment_status','i.created_at','c.full_name','i.total_paid','i.balance','i.primary_payment_mode','i.invoice_sent'];
        $sort  = in_array($filters['sort'] ?? '', $allowedSort) ? $filters['sort'] : 'i.created_at';
        $order = strtoupper($filters['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        $db     = static::db();
        $total  = (int)($db->selectOne(
            "SELECT COUNT(*) as cnt FROM invoices i LEFT JOIN clients c ON c.id = i.client_id WHERE {$where}",
            $params
        )['cnt'] ?? 0);

        $offset = ($page - 1) * $perPage;
        $sortExpr = $sort === 'i.balance' ? '(i.total_amount - i.total_paid)' : $sort;
        $sql    = "SELECT i.*, c.full_name as client_name, c.email as client_email,
                          (i.total_amount - i.total_paid) as balance,
                          (SELECT COUNT(*) FROM project_lineups pl WHERE pl.invoice_id = i.id AND pl.deleted_at IS NULL) as has_lineup
                   FROM invoices i
                   LEFT JOIN clients c ON c.id = i.client_id
                   WHERE {$where}
                   ORDER BY {$sortExpr} {$order}
                   LIMIT {$perPage} OFFSET {$offset}";
        $items  = $db->select($sql, $params);

        return [
            'data'       => $items,
            'pagination' => ['current_page' => $page, 'per_page' => $perPage, 'total' => $total, 'last_page' => (int)ceil($total / $perPage)],
        ];
    }

    public static function updatePaymentStatus(int $invoiceId): void
    {
        $invoice = static::find($invoiceId);
        if (!$invoice) return;

        $totalPaid = (float)static::db()->selectOne(
            "SELECT COALESCE(SUM(payment_amount),0) as total FROM payments WHERE invoice_id = ?",
            [$invoiceId]
        )['total'];

        $total = (float)$invoice['total_amount'];

        if ($totalPaid >= $total) {
            $status = PAYMENT_STATUS_FULLY_PAID;
        } elseif ($totalPaid > 0) {
            $status = PAYMENT_STATUS_PARTIALLY_PAID;
        } else {
            $status = PAYMENT_STATUS_UNPAID;
        }

        static::update($invoiceId, ['total_paid' => $totalPaid, 'payment_status' => $status]);
    }

    public static function searchItems(array $filters, int $page, int $perPage): array
    {
        // Match filter operates on joined rows; GROUP BY i.id collapses to 1 row per invoice.
        // Products list uses a correlated subquery so ALL products appear regardless of search term.
        $where  = "i.deleted_at IS NULL";
        $params = [];

        if (!empty($filters['search'])) {
            $term    = "%{$filters['search']}%";
            $where  .= " AND (i.invoice_number LIKE ? OR p.name LIKE ? OR p.sku LIKE ? OR c.full_name LIKE ?)";
            $params  = array_merge($params, [$term, $term, $term, $term]);
        }

        if (!empty($filters['status'])) {
            $where   .= " AND i.payment_status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['method'])) {
            $where   .= " AND i.primary_payment_mode = ?";
            $params[] = $filters['method'];
        }

        if (!empty($filters['processed_by'])) {
            $where   .= " AND i.created_by = ?";
            $params[] = (int)$filters['processed_by'];
        }

        if (!empty($filters['date_from'])) {
            $where   .= " AND DATE(i.invoice_date) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where   .= " AND DATE(i.invoice_date) <= ?";
            $params[] = $filters['date_to'];
        }

        $allowedSort = ['i.invoice_number', 'i.invoice_date', 'c.full_name', 'i.total_amount', 'i.payment_status', 'i.primary_payment_mode'];
        $sort  = in_array($filters['sort'] ?? '', $allowedSort) ? $filters['sort'] : 'i.invoice_date';
        $order = strtoupper($filters['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        $db    = static::db();
        $total = (int)($db->selectOne(
            "SELECT COUNT(DISTINCT i.id) as cnt
             FROM invoices i
             LEFT JOIN clients c ON c.id = i.client_id
             LEFT JOIN invoice_items ii ON ii.invoice_id = i.id
             LEFT JOIN products p ON p.id = ii.product_id
             WHERE {$where}",
            $params
        )['cnt'] ?? 0);

        $offset = ($page - 1) * $perPage;
        $sql = "SELECT i.id, i.invoice_number, i.invoice_date, i.payment_status,
                       i.primary_payment_mode as payment_mode, i.total_amount,
                       c.full_name as client_name, c.email as client_email,
                       CONCAT(u.first_name,' ',u.last_name) as processed_by_name,
                       (SELECT GROUP_CONCAT(CONCAT(p2.name, ' ×', ii2.quantity)
                                ORDER BY p2.name SEPARATOR ', ')
                        FROM invoice_items ii2
                        INNER JOIN products p2 ON p2.id = ii2.product_id
                        WHERE ii2.invoice_id = i.id) as products_list,
                       (SELECT COUNT(*) FROM invoice_items WHERE invoice_id = i.id) as item_count,
                       (SELECT COALESCE(SUM(quantity),0) FROM invoice_items WHERE invoice_id = i.id) as total_qty
                FROM invoices i
                LEFT JOIN clients c ON c.id = i.client_id
                LEFT JOIN users u ON u.id = i.created_by
                LEFT JOIN invoice_items ii ON ii.invoice_id = i.id
                LEFT JOIN products p ON p.id = ii.product_id
                WHERE {$where}
                GROUP BY i.id
                ORDER BY {$sort} {$order}, i.id DESC
                LIMIT {$perPage} OFFSET {$offset}";
        $items = $db->select($sql, $params);

        return [
            'data'       => $items,
            'pagination' => ['current_page' => $page, 'per_page' => $perPage, 'total' => $total, 'last_page' => (int)ceil($total / $perPage)],
        ];
    }
}
