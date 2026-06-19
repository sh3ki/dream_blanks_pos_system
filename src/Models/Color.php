<?php

namespace App\Models;

class Color extends Model
{
    protected static string $table = 'colors';
    protected static bool $softDelete = true;

    public static function allActive(): array
    {
        return static::db()->select(
            "SELECT * FROM colors WHERE status = 'active' AND deleted_at IS NULL ORDER BY `order` ASC, name ASC"
        );
    }
}
