<?php

namespace App\Models;

class StockMovement extends Model
{
    protected static string $table = 'stock_movements';
    protected static bool $timestamps = false;

    /**
     * Log a movement against a stock product.
     * $productId is the sellable product (nullable, for sale traceability).
     */
    public static function logForStockProduct(
        int $stockProductId,
        string $type,
        int $qtyChange,
        string $reason,
        ?int $referenceId,
        int $createdBy,
        ?int $productId = null
    ): int {
        return static::db()->insert('stock_movements', [
            'stock_product_id' => $stockProductId,
            'product_id'       => $productId,
            'movement_type'    => $type,
            'quantity_change'  => $qtyChange,
            'reason'           => $reason,
            'reference_id'     => $referenceId,
            'created_by'       => $createdBy,
            'created_at'       => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Legacy method — kept for backward compatibility during transition.
     * New code should use logForStockProduct().
     */
    public static function log(int $productId, string $type, int $qtyChange, string $reason, ?int $referenceId, int $createdBy): int
    {
        return static::db()->insert('stock_movements', [
            'product_id'      => $productId,
            'movement_type'   => $type,
            'quantity_change' => $qtyChange,
            'reason'          => $reason,
            'reference_id'    => $referenceId,
            'created_by'      => $createdBy,
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
    }

    /** Get movement history for a stock product. */
    public static function forStockProduct(int $stockProductId): array
    {
        return static::db()->select(
            "SELECT sm.*, CONCAT(u.first_name,' ',u.last_name) as created_by_name,
                    p.name as product_name, p.sku as product_sku
             FROM stock_movements sm
             INNER JOIN users u ON u.id = sm.created_by
             LEFT JOIN products p ON p.id = sm.product_id
             WHERE sm.stock_product_id = ?
             ORDER BY sm.created_at DESC",
            [$stockProductId]
        );
    }

    /** Legacy: get movements for a sellable product (for audit trail). */
    public static function forProduct(int $productId): array
    {
        return static::db()->select(
            "SELECT sm.*, CONCAT(u.first_name,' ',u.last_name) as created_by_name
             FROM stock_movements sm
             INNER JOIN users u ON u.id = sm.created_by
             WHERE sm.product_id = ?
             ORDER BY sm.created_at DESC",
            [$productId]
        );
    }

    /** Get all movements with stock product details, paginated. */
    public static function getAll(int $page = 1, int $perPage = 50, array $filters = []): array
    {
        $where  = "1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $term    = "%{$filters['search']}%";
            $where  .= " AND (sp.code LIKE ? OR sp.name LIKE ?)";
            $params[] = $term;
            $params[] = $term;
        }
        if (!empty($filters['movement_type'])) {
            $where   .= " AND sm.movement_type = ?";
            $params[] = $filters['movement_type'];
        }

        $db    = static::db();
        $total = (int)($db->selectOne(
            "SELECT COUNT(*) as cnt
             FROM stock_movements sm
             LEFT JOIN stock_products sp ON sp.id = sm.stock_product_id
             WHERE {$where}",
            $params
        )['cnt'] ?? 0);

        $offset = ($page - 1) * $perPage;
        $rows   = $db->select(
            "SELECT sm.*,
                    sp.code as sp_code, sp.name as sp_name,
                    t.name as type_name, c.name as color_name, s.name as size_name,
                    CONCAT(u.first_name,' ',u.last_name) as created_by_name
             FROM stock_movements sm
             LEFT JOIN stock_products sp ON sp.id = sm.stock_product_id
             LEFT JOIN types t  ON t.id  = sp.type_id
             LEFT JOIN colors c ON c.id  = sp.color_id
             LEFT JOIN sizes s  ON s.id  = sp.size_id
             INNER JOIN users u ON u.id  = sm.created_by
             WHERE {$where}
             ORDER BY sm.created_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );

        return [
            'data'       => $rows,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'total'        => $total,
                'last_page'    => (int)ceil($total / $perPage) ?: 1,
            ],
        ];
    }
}
