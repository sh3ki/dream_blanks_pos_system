<?php

namespace App\Models;

class Type extends Model
{
    protected static string $table = 'types';
    protected static bool $softDelete = true;

    public static function allActive(): array
    {
        return static::db()->select(
            "SELECT * FROM types WHERE status = 'active' AND deleted_at IS NULL ORDER BY name"
        );
    }
}
