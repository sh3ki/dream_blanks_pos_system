<?php

namespace App\Models;

/**
 * Sellable Product model.
 * Stock is NOT directly owned here — availability is computed from assigned StockProducts.
 */
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

        $allowedSort = [
            'p.sku', 'p.name', 'c.name', 'cl.name', 's.name', 't.name',
            'p.selling_price', 'p.cost_price', 'p.status', 'p.created_at', 'stock_status',
        ];
        $rawSort = $filters['sort'] ?? 'p.created_at';
        $orderBy = in_array($rawSort, $allowedSort, true) ? $rawSort : 'p.created_at';
        $dir     = strtoupper($filters['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        // stock_status is computed; translate to an expression on max_sellable
        if ($orderBy === 'stock_status') {
            $orderBy = 'COALESCE(_ss.max_sellable, 0)';
        }

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
        $sql    = "SELECT p.*, c.name as category_name, c.code as category_code, cl.name as color_name, s.name as size_name, s.code as size_code, t.name as type_name, t.code as type_code
                   FROM products p
                   LEFT JOIN categories c ON c.id = p.category_id
                   LEFT JOIN colors cl ON cl.id = p.color_id
                   LEFT JOIN sizes s ON s.id = p.size_id
                   LEFT JOIN types t ON t.id = p.type_id
                   LEFT JOIN (
                       SELECT psr.product_id,
                              MIN(FLOOR(COALESCE(sp.current_qty, 0) / NULLIF(psr.qty_required_per_unit, 0))) as max_sellable
                       FROM product_stock_requirements psr
                       INNER JOIN stock_products sp ON sp.id = psr.stock_product_id AND sp.deleted_at IS NULL
                       GROUP BY psr.product_id
                   ) _ss ON _ss.product_id = p.id
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
        // Only fetch active products that have at least one stock requirement assigned
        $where  = "p.status = 'active' AND p.deleted_at IS NULL
                   AND EXISTS (
                       SELECT 1 FROM product_stock_requirements psr
                       INNER JOIN stock_products sp ON sp.id = psr.stock_product_id
                       WHERE psr.product_id = p.id AND sp.deleted_at IS NULL
                   )";
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
        $sql   = "SELECT p.*, c.name as category_name, c.code as category_code,
                         cl.name as color_name, s.name as size_name, s.code as size_code,
                         t.name as type_name, t.code as type_code
                  FROM products p
                  LEFT JOIN categories c  ON c.id  = p.category_id
                  LEFT JOIN colors cl     ON cl.id = p.color_id
                  LEFT JOIN sizes s       ON s.id  = p.size_id
                  LEFT JOIN types t       ON t.id  = p.type_id
                  WHERE {$where}
                  ORDER BY p.name ASC
                  LIMIT {$limit}";

        $products = static::db()->select($sql, $params);

        // Augment each product with computed stock availability from stock requirements
        foreach ($products as &$product) {
            $maxSellable = StockProduct::computeMaxSellable((int)$product['id']);
            $product['computed_stock']  = $maxSellable;
            $product['current_stock']   = $maxSellable; // keep field name for POS JS compatibility
        }
        unset($product);

        // Only return products that can actually be sold right now
        return array_values(array_filter($products, fn($p) => $p['computed_stock'] > 0));
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
