<?php

namespace App\Models;

class ProductImage extends Model
{
    protected static string $table = 'product_images';
    protected static bool $timestamps = false;

    public static function forProduct(int $productId): array
    {
        return static::db()->select(
            "SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC",
            [$productId]
        );
    }
}
