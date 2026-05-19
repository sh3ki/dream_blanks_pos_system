<?php

namespace App\Models;

class Client extends Model
{
    protected static string $table = 'clients';
    protected static bool $softDelete = true;

    /**
     * Paginate clients with their primary address and contact joined.
     */
    public static function paginateWithDetails(int $page, int $perPage, string $where = '1', array $params = [], string $orderBy = 'c.created_at', string $direction = 'DESC'): array
    {
        $offset      = ($page - 1) * $perPage;
        $whereClause = "({$where}) AND c.deleted_at IS NULL";

        $total = static::db()->count('clients c', $whereClause, $params);
        $sql   = "SELECT c.*,
                    ca.street_address as primary_street, ca.city as primary_city, ca.province as primary_province,
                    cc.contact_number as primary_contact
                 FROM clients c
                 LEFT JOIN client_addresses ca ON ca.client_id = c.id AND ca.is_primary = 1 AND ca.deleted_at IS NULL
                 LEFT JOIN client_contacts cc ON cc.client_id = c.id AND cc.is_primary = 1
                 WHERE {$whereClause}
                 ORDER BY {$orderBy} {$direction}
                 LIMIT {$perPage} OFFSET {$offset}";
        $items = static::db()->select($sql, $params);

        return [
            'data'       => $items,
            'pagination' => [
                'current_page' => $page,
                'per_page'     => $perPage,
                'total'        => $total,
                'last_page'    => (int)ceil($total / $perPage),
            ],
        ];
    }

    public static function search(string $term, int $page, int $perPage, string $status = '', string $orderBy = 'c.created_at', string $direction = 'DESC'): array
    {
        $like   = "%{$term}%";
        $where  = "(c.full_name LIKE ? OR c.email LIKE ?) AND c.deleted_at IS NULL";
        $params = [$like, $like];

        if ($status) {
            $where   .= " AND c.status = ?";
            $params[] = $status;
        }

        return static::paginateWithDetails($page, $perPage, $where, $params, $orderBy, $direction);
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
