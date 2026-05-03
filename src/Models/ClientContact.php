<?php

namespace App\Models;

class ClientContact extends Model
{
    protected static string $table = 'client_contacts';

    public static function setPrimary(int $clientId, int $contactId): void
    {
        static::db()->update('client_contacts', ['is_primary' => 0], 'client_id = ?', [$clientId]);
        static::db()->update('client_contacts', ['is_primary' => 1], 'id = ?', [$contactId]);
    }

    public static function countForClient(int $clientId): int
    {
        return static::count('client_id = ?', [$clientId]);
    }
}
