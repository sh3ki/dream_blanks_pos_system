<?php

namespace App\Models;

class Transaction extends Model
{
    protected static string $table = 'transactions';
    protected static bool $softDelete = true;

    public static function generateNumber(): string
    {
        $count = static::db()->selectOne("SELECT COUNT(*) as cnt FROM transactions")['cnt'] ?? 0;
        return 'TXN-' . date('Y') . '-' . str_pad((int)$count + 1, 6, '0', STR_PAD_LEFT);
    }
}
