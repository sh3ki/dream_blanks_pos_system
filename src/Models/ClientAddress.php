<?php

namespace App\Models;

class ClientAddress extends Model
{
    protected static string $table = 'client_addresses';
    protected static bool $softDelete = true;

    public static function setPrimary(int $clientId, int $addressId): void
    {
        static::db()->update('client_addresses', ['is_primary' => 0], 'client_id = ?', [$clientId]);
        static::db()->update('client_addresses', ['is_primary' => 1], 'id = ?', [$addressId]);
    }

    public static function countForClient(int $clientId): int
    {
        return static::count('client_id = ? AND deleted_at IS NULL', [$clientId]);
    }
}
