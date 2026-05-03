<?php

namespace App\Models;

class Inventory extends Model
{
    protected static string $table = 'inventory';
    protected static bool $timestamps = false;

    public static function findByProduct(int $productId): ?array
    {
        return static::whereOne('product_id', $productId);
    }

    public static function getLowStock(): array
    {
        return static::db()->select(
            "SELECT i.*, p.name, p.sku, p.low_stock_alert
             FROM inventory i
             INNER JOIN products p ON p.id = i.product_id
             WHERE i.stock_status IN ('low_stock','out_of_stock') AND p.deleted_at IS NULL
             ORDER BY i.quantity_on_hand ASC"
        );
    }

    public static function getAll(array $filters, int $page, int $perPage): array
    {
        $where  = "p.deleted_at IS NULL";
        $params = [];

        if (!empty($filters['search'])) {
            $term    = "%{$filters['search']}%";
            $where  .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
            $params  = [$term, $term];
        }

        if (!empty($filters['status'])) {
            $where   .= " AND i.stock_status = ?";
            $params[] = $filters['status'];
        }

        $allowedSort = ['p.name','p.sku','i.quantity_on_hand','i.stock_status','i.last_updated'];
        $sort  = in_array($filters['sort'] ?? '', $allowedSort) ? $filters['sort'] : 'i.stock_status';
        $order = strtoupper($filters['order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
        // secondary sort
        $orderClause = $sort === 'i.stock_status' ? "{$sort} {$order}, p.name ASC" : "{$sort} {$order}";

        $db    = static::db();
        $total = (int)($db->selectOne(
            "SELECT COUNT(*) as cnt FROM inventory i INNER JOIN products p ON p.id = i.product_id WHERE {$where}",
            $params
        )['cnt'] ?? 0);

        $offset = ($page - 1) * $perPage;
        $sql    = "SELECT i.*, p.name, p.sku, p.low_stock_alert, c.name as category_name
                   FROM inventory i
                   INNER JOIN products p ON p.id = i.product_id
                   LEFT JOIN categories c ON c.id = p.category_id
                   WHERE {$where}
                   ORDER BY {$orderClause}
                   LIMIT {$perPage} OFFSET {$offset}";
        $items  = $db->select($sql, $params);

        return [
            'data'       => $items,
            'pagination' => ['current_page' => $page, 'per_page' => $perPage, 'total' => $total, 'last_page' => (int)ceil($total / $perPage)],
        ];
    }
}
