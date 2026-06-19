<?php

namespace App\Models;

class Category extends Model
{
    protected static string $table = 'categories';
    protected static bool $softDelete = true;

    public static function allActive(): array
    {
        return static::db()->select(
            "SELECT * FROM categories WHERE status = 'active' AND deleted_at IS NULL ORDER BY `order` ASC, name ASC"
        );
    }
}
