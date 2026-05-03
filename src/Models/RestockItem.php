<?php

namespace App\Models;

class RestockItem extends Model
{
    protected static string $table = 'restock_items';

    public static function forRestock(int $restockId): array
    {
        return static::db()->select(
            "SELECT * FROM restock_items WHERE restock_id = ?",
            [$restockId]
        );
    }
}
