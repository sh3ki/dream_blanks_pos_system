<?php

namespace App\Models;

class StockMovement extends Model
{
    protected static string $table = 'stock_movements';
    protected static bool $timestamps = false;

    public static function log(int $productId, string $type, int $qtyChange, string $reason, ?int $referenceId, int $createdBy): int
    {
        return static::db()->insert('stock_movements', [
            'product_id'     => $productId,
            'movement_type'  => $type,
            'quantity_change' => $qtyChange,
            'reason'         => $reason,
            'reference_id'   => $referenceId,
            'created_by'     => $createdBy,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);
    }

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
}
