<?php

namespace App\Models;

class RestockOrder extends Model
{
    protected static string $table = 'restock_orders';

    public static function getItems(int $restockId): array
    {
        return static::db()->select(
            "SELECT ri.*, p.name, p.sku, p.current_stock
             FROM restock_items ri
             INNER JOIN products p ON p.id = ri.product_id
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
}
