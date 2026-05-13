<?php

namespace App\Models;

class Inventory extends Model
{
    protected static string $table = 'inventory';
    protected static bool $timestamps = false;

    public static function findByStockProduct(int $stockProductId): ?array
    {
        return static::db()->selectOne(
            "SELECT i.*, sp.code, sp.name, sp.low_stock_alert,
                    t.name as type_name, c.name as color_name, s.name as size_name
             FROM inventory i
             INNER JOIN stock_products sp ON sp.id = i.stock_product_id
             LEFT JOIN types t  ON t.id  = sp.type_id
             LEFT JOIN colors c ON c.id  = sp.color_id
             LEFT JOIN sizes s  ON s.id  = sp.size_id
             WHERE i.stock_product_id = ?",
            [$stockProductId]
        );
    }

    /** Legacy — kept for backward compatibility during transition. */
    public static function findByProduct(int $productId): ?array
    {
        return static::whereOne('product_id', $productId);
    }

    public static function getLowStock(): array
    {
        return static::db()->select(
            "SELECT i.*, sp.code, sp.name, sp.low_stock_alert,
                    t.name as type_name, c.name as color_name, s.name as size_name
             FROM inventory i
             INNER JOIN stock_products sp ON sp.id = i.stock_product_id
             LEFT JOIN types t  ON t.id  = sp.type_id
             LEFT JOIN colors c ON c.id  = sp.color_id
             LEFT JOIN sizes s  ON s.id  = sp.size_id
             WHERE i.stock_status IN ('low_stock','out_of_stock') AND sp.deleted_at IS NULL
             ORDER BY i.quantity_on_hand ASC"
        );
    }

    public static function getAll(array $filters, int $page, int $perPage): array
    {
        $where  = "sp.deleted_at IS NULL";
        $params = [];

        if (!empty($filters['search'])) {
            $term    = "%{$filters['search']}%";
            $where  .= " AND (sp.name LIKE ? OR sp.code LIKE ?)";
            $params  = [$term, $term];
        }

        if (!empty($filters['status'])) {
            $where   .= " AND i.stock_status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['type_id'])) {
            $where   .= " AND sp.type_id = ?";
            $params[] = $filters['type_id'];
        }

        if (!empty($filters['color_id'])) {
            $where   .= " AND sp.color_id = ?";
            $params[] = $filters['color_id'];
        }

        if (!empty($filters['size_id'])) {
            $where   .= " AND sp.size_id = ?";
            $params[] = $filters['size_id'];
        }

        $allowedSort = ['sp.name','sp.code','i.quantity_on_hand','i.stock_status','i.last_updated','t.name','c.name','s.name'];
        $sort  = in_array($filters['sort'] ?? '', $allowedSort) ? $filters['sort'] : 'i.stock_status';
        $order = strtoupper($filters['order'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
        $orderClause = $sort === 'i.stock_status' ? "{$sort} {$order}, sp.name ASC" : "{$sort} {$order}";

        $db    = static::db();
        $total = (int)($db->selectOne(
            "SELECT COUNT(*) as cnt
             FROM inventory i
             INNER JOIN stock_products sp ON sp.id = i.stock_product_id
             WHERE {$where}",
            $params
        )['cnt'] ?? 0);

        $offset = ($page - 1) * $perPage;
        $sql    = "SELECT i.*, sp.code, sp.name, sp.low_stock_alert,
                          t.name as type_name, c.name as color_name, s.name as size_name
                   FROM inventory i
                   INNER JOIN stock_products sp ON sp.id = i.stock_product_id
                   LEFT JOIN types t  ON t.id  = sp.type_id
                   LEFT JOIN colors c ON c.id  = sp.color_id
                   LEFT JOIN sizes s  ON s.id  = sp.size_id
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
