<?php

namespace App\Models;

class RestockOrder extends Model
{
    protected static string $table = 'restock_orders';

    public static function getItems(int $restockId): array
    {
        return static::db()->select(
            "SELECT ri.*,
                    sp.name, sp.code,
                    sp.current_qty,
                    t.name  as type_name,
                    c.name  as color_name,
                    s.name  as size_name
             FROM restock_items ri
             INNER JOIN stock_products sp ON sp.id = ri.stock_product_id
             LEFT  JOIN types  t ON t.id  = sp.type_id
             LEFT  JOIN colors c ON c.id  = sp.color_id
             LEFT  JOIN sizes  s ON s.id  = sp.size_id
             WHERE ri.restock_id = ?",
            [$restockId]
        );
    }

    public static function generateOrderNumber(): string
    {
        $count = static::db()->selectOne("SELECT COUNT(*) as cnt FROM restock_orders")['cnt'] ?? 0;
        return 'RO-' . date('Y') . '-' . str_pad((int)$count + 1, 4, '0', STR_PAD_LEFT);
    }

    public static function getWithItems(int $id): ?array
    {
        $order = static::find($id);
        if (!$order) return null;
        $order['items'] = static::getItems($id);
        return $order;
    }

    public static function getRecent(int $limit = 20): array
    {
        return static::db()->select(
            "SELECT ro.*,
                    CONCAT(u.first_name,' ',u.last_name) as created_by_name,
                    COUNT(ri.id) as items_count
             FROM restock_orders ro
             INNER JOIN users u ON u.id = ro.created_by
             LEFT JOIN restock_items ri ON ri.restock_id = ro.id
             GROUP BY ro.id
             ORDER BY ro.created_at DESC
             LIMIT " . (int)$limit,
            []
        );
    }

    public static function paginated(int $page, int $perPage, array $filters = []): array
    {
        $allowed = ['ro.order_number', 'ro.order_date', 'ro.supplier_name', 'ro.delivery_status', 'ro.created_at'];
        $sort    = in_array($filters['restock_sort'] ?? '', $allowed) ? $filters['restock_sort'] : 'ro.created_at';
        $dir     = strtoupper($filters['restock_order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        $where  = '1=1';
        $params = [];
        $allowedStatuses = ['ordered', 'delivered', 'incomplete', 'problematic'];
        if (!empty($filters['restock_status']) && in_array($filters['restock_status'], $allowedStatuses, true)) {
            $where   .= ' AND ro.delivery_status = ?';
            $params[] = $filters['restock_status'];
        }

        $db    = static::db();
        $total = (int)($db->selectOne("SELECT COUNT(*) as cnt FROM restock_orders ro WHERE {$where}", $params)['cnt'] ?? 0);
        $offset = ($page - 1) * $perPage;

        $rows = $db->select(
            "SELECT ro.*,
                    CONCAT(u.first_name,' ',u.last_name) as created_by_name,
                    COUNT(ri.id) as items_count
             FROM restock_orders ro
             INNER JOIN users u ON u.id = ro.created_by
             LEFT  JOIN restock_items ri ON ri.restock_id = ro.id
             WHERE {$where}
             GROUP BY ro.id
             ORDER BY {$sort} {$dir}
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return [
            'data'       => $rows,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'total'        => $total,
                'last_page'    => (int)ceil($total / max(1, $perPage)),
            ],
        ];
    }
}
