<?php

namespace App\Models;

class ProductStockRequirement extends Model
{
    protected static string $table = 'product_stock_requirements';
    protected static bool $timestamps = true;

    /** Return all requirements for a sellable product, joined with stock product details. */
    public static function forProduct(int $productId): array
    {
        return static::db()->select(
            "SELECT psr.*, sp.code as stock_product_code, sp.name as stock_product_name,
                    sp.current_qty, t.name as type_name, c.name as color_name, s.name as size_name
             FROM product_stock_requirements psr
             INNER JOIN stock_products sp ON sp.id = psr.stock_product_id
             LEFT JOIN types t  ON t.id  = sp.type_id
             LEFT JOIN colors c ON c.id  = sp.color_id
             LEFT JOIN sizes s  ON s.id  = sp.size_id
             WHERE psr.product_id = ? AND sp.deleted_at IS NULL
             ORDER BY sp.name ASC",
            [$productId]
        );
    }

    /**
     * Replace all requirements for a product with the provided array.
     * Each item: ['stock_product_id' => int, 'qty_required_per_unit' => float, 'waste_percent' => float]
     */
    public static function saveForProduct(int $productId, array $requirements): void
    {
        $db = static::db();

        // Delete existing requirements
        $db->delete(static::$table, 'product_id = ?', [$productId]);

        foreach ($requirements as $req) {
            $stockProductId = (int)($req['stock_product_id'] ?? 0);
            $qtyPerUnit     = (float)($req['qty_required_per_unit'] ?? 1);
            $wastePercent   = (float)($req['waste_percent'] ?? 0);

            if ($stockProductId <= 0 || $qtyPerUnit <= 0) {
                continue;
            }

            $db->insert(static::$table, [
                'product_id'           => $productId,
                'stock_product_id'     => $stockProductId,
                'qty_required_per_unit'=> $qtyPerUnit,
                'waste_percent'        => $wastePercent,
                'created_at'           => date('Y-m-d H:i:s'),
                'updated_at'           => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /** Check if a product has at least one requirement assigned. */
    public static function hasRequirements(int $productId): bool
    {
        $count = (int)(static::db()->selectOne(
            "SELECT COUNT(*) as cnt FROM product_stock_requirements WHERE product_id = ?",
            [$productId]
        )['cnt'] ?? 0);

        return $count > 0;
    }

    /** Return all products that use a given stock product. */
    public static function productsUsing(int $stockProductId): array
    {
        return static::db()->select(
            "SELECT p.id, p.name, p.sku, psr.qty_required_per_unit, psr.waste_percent
             FROM product_stock_requirements psr
             INNER JOIN products p ON p.id = psr.product_id
             WHERE psr.stock_product_id = ? AND p.deleted_at IS NULL
             ORDER BY p.name ASC",
            [$stockProductId]
        );
    }
}
