<?php

namespace App\Models;

class Client extends Model
{
    protected static string $table = 'clients';
    protected static bool $softDelete = true;

    public static function search(string $term, int $page, int $perPage, string $status = ''): array
    {
        $where  = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ?) AND deleted_at IS NULL";
        $params = ["%{$term}%", "%{$term}%", "%{$term}%"];

        if ($status) {
            $where   .= " AND status = ?";
            $params[] = $status;
        }

        return static::paginate($page, $perPage, $where, $params, 'created_at', 'DESC');
    }

    public static function getAddresses(int $clientId): array
    {
        return static::db()->select(
            "SELECT * FROM client_addresses WHERE client_id = ? AND deleted_at IS NULL ORDER BY is_primary DESC",
            [$clientId]
        );
    }

    public static function getContacts(int $clientId): array
    {
        return static::db()->select(
            "SELECT * FROM client_contacts WHERE client_id = ? ORDER BY is_primary DESC",
            [$clientId]
        );
    }

    public static function getWithDetails(int $clientId): ?array
    {
        $client = static::find($clientId);
        if (!$client) return null;

        $client['addresses'] = static::getAddresses($clientId);
        $client['contacts']  = static::getContacts($clientId);
        return $client;
    }

    public static function getInvoiceHistory(int $clientId): array
    {
        return static::db()->select(
            "SELECT * FROM invoices WHERE client_id = ? AND deleted_at IS NULL ORDER BY created_at DESC",
            [$clientId]
        );
    }
}
