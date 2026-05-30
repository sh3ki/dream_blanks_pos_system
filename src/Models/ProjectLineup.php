<?php

namespace App\Models;

class ProjectLineup extends Model
{
    protected static string $table = 'project_lineups';
    protected static bool $softDelete = true;

    public static function search(array $filters, int $page, int $perPage): array
    {
        $where  = "pl.deleted_at IS NULL";
        $params = [];

        if (!empty($filters['search'])) {
            $term    = "%{$filters['search']}%";
            $where  .= " AND (i.invoice_number LIKE ? OR COALESCE(pl.client_name, c.full_name) LIKE ? OR pl.brand_name LIKE ?)";
            $params  = array_merge($params, [$term, $term, $term]);
        }

        if (!empty($filters['date_from'])) {
            $where   .= " AND pl.date >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where   .= " AND pl.date <= ?";
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['client_id'])) {
            $where   .= " AND i.client_id = ?";
            $params[] = (int)$filters['client_id'];
        }

        if (!empty($filters['category'])) {
            $term     = "%" . $filters['category'] . "%";
            $where   .= " AND pl.categories LIKE ?";
            $params[] = $term;
        }

        if (!empty($filters['type'])) {
            $term     = "%" . $filters['type'] . "%";
            $where   .= " AND pl.types LIKE ?";
            $params[] = $term;
        }

        if (!empty($filters['project_status'])) {
            $where   .= " AND pl.project_status = ?";
            $params[] = $filters['project_status'];
        }

        $allowedSorts = [
            'pl.date', 'i.invoice_number', 'c.full_name', 'pl.brand_name',
            'pl.qty',  'pl.link',          'pl.deadline', 'pl.project_status',
        ];
        $sort  = in_array($filters['sort'] ?? '', $allowedSorts) ? $filters['sort'] : 'pl.id';
        $order = strtoupper($filters['order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

        $db    = static::db();
        $total = (int)($db->selectOne(
            "SELECT COUNT(*) as cnt
             FROM project_lineups pl
             LEFT JOIN invoices i ON i.id = pl.invoice_id
             LEFT JOIN clients c ON c.id = i.client_id
             WHERE {$where}",
            $params
        )['cnt'] ?? 0);

        $offset = ($page - 1) * $perPage;
        $sql    = "SELECT pl.*,
                          i.invoice_number,
                          COALESCE(pl.client_name, c.full_name, 'Walk-in') as client_name,
                          (SELECT COUNT(*) FROM project_lineups pl2
                           WHERE pl2.deleted_at IS NULL AND pl2.id <= pl.id) as queue_number
                   FROM project_lineups pl
                   LEFT JOIN invoices i ON i.id = pl.invoice_id
                   LEFT JOIN clients c ON c.id = i.client_id
                   WHERE {$where}
                   ORDER BY {$sort} {$order}
                   LIMIT {$perPage} OFFSET {$offset}";
        $items  = $db->select($sql, $params);

        return [
            'data'       => $items,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'total'        => $total,
                'last_page'    => (int)ceil($total / $perPage),
            ],
        ];
    }

    /**
     * Get pre-fill data for the add modal from a given invoice.
     */
    public static function getInvoicePrefill(int $invoiceId): ?array
    {
        $db = static::db();

        $invoice = $db->selectOne(
            "SELECT i.id, i.invoice_number, DATE(i.invoice_date) as date,
                    COALESCE(c.full_name, 'Walk-in') as client_name,
                    i.brand_name,
                    SUM(ii.quantity) as total_qty
             FROM invoices i
             LEFT JOIN clients c ON c.id = i.client_id
             LEFT JOIN invoice_items ii ON ii.invoice_id = i.id
             WHERE i.id = ? AND i.deleted_at IS NULL
             GROUP BY i.id",
            [$invoiceId]
        );

        if (!$invoice) return null;

        $categories = $db->select(
            "SELECT DISTINCT cat.code
             FROM invoice_items ii
             INNER JOIN products p ON p.id = ii.product_id
             LEFT JOIN categories cat ON cat.id = p.category_id
             WHERE ii.invoice_id = ? AND cat.code IS NOT NULL",
            [$invoiceId]
        );

        $types = $db->select(
            "SELECT DISTINCT t.code
             FROM invoice_items ii
             INNER JOIN products p ON p.id = ii.product_id
             LEFT JOIN types t ON t.id = p.type_id
             WHERE ii.invoice_id = ? AND t.code IS NOT NULL",
            [$invoiceId]
        );

        $invoice['categories'] = array_column($categories, 'code');
        $invoice['types']      = array_column($types, 'code');

        return $invoice;
    }

    /**
     * Check if an invoice already has a project lineup entry.
     */
    public static function invoiceHasLineup(int $invoiceId): bool
    {
        $row = static::db()->selectOne(
            "SELECT id FROM project_lineups WHERE invoice_id = ? AND deleted_at IS NULL LIMIT 1",
            [$invoiceId]
        );
        return $row !== null;
    }
}
