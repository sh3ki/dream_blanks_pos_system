<?php

namespace App\Models;

class StockProduct extends Model
{
    protected static string $table = 'stock_products';
    protected static bool $softDelete = true;

    /** Paginated search with optional filters. */
    public static function search(array $filters, int $page, int $perPage): array
    {
        $where  = "sp.deleted_at IS NULL";
        $params = [];

        if (!empty($filters['search'])) {
            $term    = "%{$filters['search']}%";
            $where  .= " AND (sp.name LIKE ? OR sp.code LIKE ?)";
            $params  = [$term, $term];
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

        if (!empty($filters['status'])) {
            $where   .= " AND sp.status = ?";
            $params[] = $filters['status'];
        }

        // stock_status filter: computed from current_qty vs low_stock_alert
        if (!empty($filters['stock_status'])) {
            switch ($filters['stock_status']) {
                case 'out_of_stock':
                    $where .= " AND sp.current_qty <= 0";
                    break;
                case 'low_stock':
                    $where .= " AND sp.current_qty > 0 AND sp.current_qty <= sp.low_stock_alert";
                    break;
                case 'in_stock':
                    $where .= " AND sp.current_qty > sp.low_stock_alert";
                    break;
            }
        }

        $allowed = ['sp.name', 'sp.code', 'sp.current_qty', 'sp.status', 'sp.created_at'];
        $sort    = in_array($filters['sort'] ?? '', $allowed) ? $filters['sort'] : 'sp.created_at';
        $dir     = strtoupper($filters['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        $db    = static::db();
        $total = (int)($db->selectOne(
            "SELECT COUNT(*) as cnt
             FROM stock_products sp
             LEFT JOIN types t  ON t.id  = sp.type_id
             LEFT JOIN colors c ON c.id  = sp.color_id
             LEFT JOIN sizes s  ON s.id  = sp.size_id
             WHERE {$where}",
            $params
        )['cnt'] ?? 0);

        $offset = ($page - 1) * $perPage;
        $sql    = "SELECT sp.*, t.name as type_name, t.code as type_code,
                          c.name as color_name, c.hex_code,
                          s.name as size_name, s.code as size_code
                   FROM stock_products sp
                   LEFT JOIN types t  ON t.id  = sp.type_id
                   LEFT JOIN colors c ON c.id  = sp.color_id
                   LEFT JOIN sizes s  ON s.id  = sp.size_id
                   WHERE {$where}
                   ORDER BY {$sort} {$dir}
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

    /** Return all active stock products for dropdowns. */
    public static function allActive(): array
    {
        return static::db()->select(
            "SELECT sp.*, t.name as type_name, c.name as color_name, s.name as size_name
             FROM stock_products sp
             LEFT JOIN types t  ON t.id  = sp.type_id
             LEFT JOIN colors c ON c.id  = sp.color_id
             LEFT JOIN sizes s  ON s.id  = sp.size_id
             WHERE sp.status = 'active' AND sp.deleted_at IS NULL
             ORDER BY sp.name ASC"
        );
    }

    /** Find with joined type/color/size detail. */
    public static function findWithDetails(int $id): ?array
    {
        return static::db()->selectOne(
            "SELECT sp.*, t.name as type_name, t.code as type_code,
                    c.name as color_name, c.hex_code,
                    s.name as size_name, s.code as size_code
             FROM stock_products sp
             LEFT JOIN types t  ON t.id  = sp.type_id
             LEFT JOIN colors c ON c.id  = sp.color_id
             LEFT JOIN sizes s  ON s.id  = sp.size_id
             WHERE sp.id = ? AND sp.deleted_at IS NULL",
            [$id]
        );
    }

    /** Decrement current_qty and sync inventory record. */
    public static function decrementQty(int $stockProductId, int $qty): void
    {
        static::db()->query(
            "UPDATE stock_products SET current_qty = current_qty - ? WHERE id = ?",
            [$qty, $stockProductId]
        );
        static::syncInventoryStatus($stockProductId);
    }

    /** Increment current_qty and sync inventory record. */
    public static function incrementQty(int $stockProductId, int $qty): void
    {
        static::db()->query(
            "UPDATE stock_products SET current_qty = current_qty + ? WHERE id = ?",
            [$qty, $stockProductId]
        );
        static::syncInventoryStatus($stockProductId);
    }

    /** Recompute and persist stock_status in the inventory table. */
    public static function syncInventoryStatus(int $stockProductId): void
    {
        $sp = static::find($stockProductId);
        if (!$sp) {
            return;
        }

        $qty   = (int)$sp['current_qty'];
        $alert = (int)($sp['low_stock_alert'] ?? 10);

        if ($qty <= 0) {
            $status = STOCK_OUT;
        } elseif ($qty <= $alert) {
            $status = STOCK_LOW_STOCK;
        } else {
            $status = STOCK_IN_STOCK;
        }

        $db = static::db();
        $existing = $db->selectOne(
            "SELECT id FROM inventory WHERE stock_product_id = ?",
            [$stockProductId]
        );

        if ($existing) {
            $db->update(
                'inventory',
                ['quantity_on_hand' => $qty, 'stock_status' => $status],
                'stock_product_id = ?',
                [$stockProductId]
            );
        } else {
            $db->insert('inventory', [
                'stock_product_id'  => $stockProductId,
                'quantity_on_hand'  => $qty,
                'quantity_reserved' => 0,
                'stock_status'      => $status,
            ]);
        }
    }

    /** Return low-stock and out-of-stock records. */
    public static function getLowStock(): array
    {
        return static::db()->select(
            "SELECT sp.*, t.name as type_name, c.name as color_name, s.name as size_name,
                    i.stock_status
             FROM stock_products sp
             INNER JOIN inventory i ON i.stock_product_id = sp.id
             LEFT JOIN types t  ON t.id  = sp.type_id
             LEFT JOIN colors c ON c.id  = sp.color_id
             LEFT JOIN sizes s  ON s.id  = sp.size_id
             WHERE i.stock_status IN ('low_stock','out_of_stock') AND sp.deleted_at IS NULL
             ORDER BY sp.current_qty ASC"
        );
    }

    /**
     * Compute the maximum sellable quantity of a product given the current
     * stock of its required stock products.
     * Returns 0 if the product has no requirements assigned.
     */
    public static function computeMaxSellable(int $productId): int
    {
        $reqs = ProductStockRequirement::forProduct($productId);
        if (empty($reqs)) {
            return 0;
        }

        $max = PHP_INT_MAX;
        foreach ($reqs as $req) {
            $sp           = static::find((int)$req['stock_product_id']);
            $qty          = (int)($sp['current_qty'] ?? 0);
            $perUnit      = (float)$req['qty_required_per_unit'];
            $waste        = (float)($req['waste_percent'] ?? 0);
            $effectivePer = $perUnit * (1 + $waste / 100);

            if ($effectivePer <= 0) {
                continue;
            }

            $canMake = (int)floor($qty / $effectivePer);
            $max     = min($max, $canMake);
        }

        return $max === PHP_INT_MAX ? 0 : $max;
    }

    /**
     * Compute max sellable for a batch of products in a single query.
     * Returns [product_id => max_sellable_qty].
     */
    public static function computeMaxSellableForProducts(array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));

        // For each (product, stock_product) pair compute floor(qty / effective_per),
        // then take the MIN per product (bottleneck requirement).
        $rows = static::db()->select(
            "SELECT psr.product_id,
                    FLOOR(MIN(
                        sp.current_qty / (psr.qty_required_per_unit * (1 + psr.waste_percent / 100))
                    )) AS max_sellable,
                    MIN(sp.low_stock_alert) AS min_alert
             FROM product_stock_requirements psr
             JOIN stock_products sp ON sp.id = psr.stock_product_id AND sp.deleted_at IS NULL
             WHERE psr.product_id IN ({$placeholders})
               AND psr.qty_required_per_unit > 0
             GROUP BY psr.product_id",
            $productIds
        );

        $map = [];
        foreach ($rows as $row) {
            $qty   = (int)$row['max_sellable'];
            $alert = (int)($row['min_alert'] ?? 10);
            $map[(int)$row['product_id']] = [
                'computed_stock' => $qty,
                'stock_status'   => $qty <= 0 ? 'out_of_stock' : ($qty <= $alert ? 'low_stock' : 'in_stock'),
            ];
        }

        // Products with no requirements get 0 / out_of_stock
        foreach ($productIds as $id) {
            if (!isset($map[$id])) {
                $map[$id] = ['computed_stock' => 0, 'stock_status' => 'out_of_stock'];
            }
        }

        return $map;
    }

    /** Soft delete. */
    public static function delete(int $id): int
    {
        return static::db()->update(
            static::$table,
            ['deleted_at' => date('Y-m-d H:i:s')],
            'id = ?',
            [$id]
        );
    }
}
