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
}
