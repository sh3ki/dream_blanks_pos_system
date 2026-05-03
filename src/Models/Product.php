<?php

namespace App\Models;

class Product extends Model
{
    protected static string $table = 'products';
    protected static bool $softDelete = true;

    public static function search(array $filters, int $page, int $perPage): array
    {
        $where  = "p.deleted_at IS NULL";
        $params = [];

        if (!empty($filters['search'])) {
            $where   .= " AND (p.name LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ?)";
            $term     = "%{$filters['search']}%";
            $params   = array_merge($params, [$term, $term, $term]);
        }

        if (!empty($filters['category_id'])) {
            $where   .= " AND p.category_id = ?";
            $params[] = $filters['category_id'];
        }

        if (!empty($filters['type_id'])) {
            $where   .= " AND p.type_id = ?";
            $params[] = $filters['type_id'];
        }

        if (!empty($filters['color_id'])) {
            $where   .= " AND p.color_id = ?";
            $params[] = $filters['color_id'];
        }

        if (!empty($filters['size_id'])) {
            $where   .= " AND p.size_id = ?";
            $params[] = $filters['size_id'];
        }

        if (!empty($filters['status'])) {
            $where   .= " AND p.status = ?";
            $params[] = $filters['status'];
        }

        $orderBy = $filters['sort'] ?? 'p.created_at';
        $dir     = strtoupper($filters['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        $db = static::db();
        $countSql = "SELECT COUNT(*) as cnt
                     FROM products p
                     LEFT JOIN categories c ON c.id = p.category_id
                     LEFT JOIN colors cl ON cl.id = p.color_id
                     LEFT JOIN sizes s ON s.id = p.size_id
                     LEFT JOIN types t ON t.id = p.type_id
                     WHERE {$where}";
        $total = (int)($db->selectOne($countSql, $params)['cnt'] ?? 0);

        $offset = ($page - 1) * $perPage;
        $sql    = "SELECT p.*, c.name as category_name, cl.name as color_name, s.name as size_name, t.name as type_name
                   FROM products p
                   LEFT JOIN categories c ON c.id = p.category_id
                   LEFT JOIN colors cl ON cl.id = p.color_id
                   LEFT JOIN sizes s ON s.id = p.size_id
                   LEFT JOIN types t ON t.id = p.type_id
                   WHERE {$where}
                   ORDER BY {$orderBy} {$dir}
                   LIMIT {$perPage} OFFSET {$offset}";
        $items = $db->select($sql, $params);

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

    public static function findWithDetails(int $id): ?array
    {
        $product = static::db()->selectOne(
            "SELECT p.*, c.name as category_name, cl.name as color_name, s.name as size_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN colors cl ON cl.id = p.color_id
             LEFT JOIN sizes s ON s.id = p.size_id
             WHERE p.id = ? AND p.deleted_at IS NULL",
            [$id]
        );

        return $product;
    }

    public static function forPos(array $filters = []): array
    {
        $where  = "p.status = 'active' AND p.deleted_at IS NULL AND p.current_stock > 0";
        $params = [];

        if (!empty($filters['search'])) {
            $term    = "%{$filters['search']}%";
            $where  .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
            $params  = array_merge($params, [$term, $term]);
        }

        if (!empty($filters['category_id'])) {
            $where   .= " AND p.category_id = ?";
            $params[] = $filters['category_id'];
        }

        if (!empty($filters['type_id'])) {
            $where   .= " AND p.type_id = ?";
            $params[] = $filters['type_id'];
        }

        if (!empty($filters['color_id'])) {
            $where   .= " AND p.color_id = ?";
            $params[] = $filters['color_id'];
        }

        if (!empty($filters['size_id'])) {
            $where   .= " AND p.size_id = ?";
            $params[] = $filters['size_id'];
        }

        $limit = (int)($filters['limit'] ?? 200);
        $sql   = "SELECT p.*, c.name as category_name, c.code as category_code, cl.name as color_name, s.name as size_name, s.code as size_code, t.name as type_name, t.code as type_code
                  FROM products p
                  LEFT JOIN categories c ON c.id = p.category_id
                  LEFT JOIN colors cl ON cl.id = p.color_id
                  LEFT JOIN sizes s ON s.id = p.size_id
                  LEFT JOIN types t ON t.id = p.type_id
                  WHERE {$where}
                  ORDER BY p.name ASC
                  LIMIT {$limit}";

        return static::db()->select($sql, $params);
    }

    public static function decrementStock(int $productId, int $qty): void
    {
        static::db()->query(
            "UPDATE products SET current_stock = current_stock - ? WHERE id = ?",
            [$qty, $productId]
        );
        static::updateStockStatus($productId);
    }

    public static function incrementStock(int $productId, int $qty): void
    {
        static::db()->query(
            "UPDATE products SET current_stock = current_stock + ? WHERE id = ?",
            [$qty, $productId]
        );
        static::updateStockStatus($productId);
    }

    public static function updateStockStatus(int $productId): void
    {
        $product = static::find($productId);
        if (!$product) return;

        $stock = (int)$product['current_stock'];
        $alert = (int)($product['low_stock_alert'] ?? 10);

        if ($stock <= 0) {
            $status = STOCK_OUT;
        } elseif ($stock <= $alert) {
            $status = STOCK_LOW_STOCK;
        } else {
            $status = STOCK_IN_STOCK;
        }

        static::db()->update(
            'inventory',
            ['quantity_on_hand' => $stock, 'stock_status' => $status],
            'product_id = ?',
            [$productId]
        );
    }

    public static function findBySku(string $sku): ?array
    {
        return static::whereOne('sku', $sku);
    }

    public static function delete(int $id): int
    {
        return static::db()->delete(static::$table, 'id = ?', [$id]);
    }
}
