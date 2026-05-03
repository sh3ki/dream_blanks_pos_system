<?php

namespace App\Models;

use App\Core\Database;

abstract class Model
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';
    protected static bool $timestamps = true;
    protected static bool $softDelete = false;

    protected Database $db;
    protected array $attributes = [];

    public function __construct(array $attributes = [])
    {
        $this->db = Database::getInstance();
        $this->attributes = $attributes;
    }

    public function __get(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    public function __set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    public function toArray(): array
    {
        return $this->attributes;
    }

    // ---- Static query helpers ----

    public static function db(): Database
    {
        return Database::getInstance();
    }

    public static function find(int $id): ?array
    {
        $table = static::$table;
        $pk    = static::$primaryKey;
        $sql   = "SELECT * FROM {$table} WHERE {$pk} = ?";

        if (static::$softDelete) {
            $sql .= " AND deleted_at IS NULL";
        }

        return static::db()->selectOne($sql, [$id]);
    }

    public static function findOrFail(int $id): array
    {
        $record = static::find($id);
        if (!$record) {
            throw new \App\Exceptions\NotFoundException(static::$table . ' not found');
        }
        return $record;
    }

    public static function all(string $orderBy = 'id', string $direction = 'ASC'): array
    {
        $table = static::$table;
        $sql   = "SELECT * FROM {$table}";

        if (static::$softDelete) {
            $sql .= " WHERE deleted_at IS NULL";
        }

        $sql .= " ORDER BY {$orderBy} {$direction}";
        return static::db()->select($sql);
    }

    public static function create(array $data): int
    {
        if (static::$timestamps) {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        return static::db()->insert(static::$table, $data);
    }

    public static function update(int $id, array $data): int
    {
        if (static::$timestamps) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        return static::db()->update(static::$table, $data, static::$primaryKey . ' = ?', [$id]);
    }

    public static function delete(int $id): int
    {
        if (static::$softDelete) {
            return static::db()->update(static::$table, ['deleted_at' => date('Y-m-d H:i:s')], static::$primaryKey . ' = ?', [$id]);
        }
        return static::db()->delete(static::$table, static::$primaryKey . ' = ?', [$id]);
    }

    public static function paginate(int $page, int $perPage, string $where = '1', array $params = [], string $orderBy = 'id', string $direction = 'DESC'): array
    {
        $table  = static::$table;
        $offset = ($page - 1) * $perPage;

        $whereClause = $where;
        if (static::$softDelete) {
            $whereClause = "({$where}) AND deleted_at IS NULL";
        }

        $total = static::db()->count($table, $whereClause, $params);
        $sql   = "SELECT * FROM {$table} WHERE {$whereClause} ORDER BY {$orderBy} {$direction} LIMIT {$perPage} OFFSET {$offset}";
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

    public static function where(string $column, mixed $value, string $operator = '='): array
    {
        $table = static::$table;
        $sql   = "SELECT * FROM {$table} WHERE {$column} {$operator} ?";
        if (static::$softDelete) {
            $sql .= " AND deleted_at IS NULL";
        }
        return static::db()->select($sql, [$value]);
    }

    public static function whereOne(string $column, mixed $value, string $operator = '='): ?array
    {
        $table = static::$table;
        $sql   = "SELECT * FROM {$table} WHERE {$column} {$operator} ?";
        if (static::$softDelete) {
            $sql .= " AND deleted_at IS NULL";
        }
        $sql .= " LIMIT 1";
        return static::db()->selectOne($sql, [$value]);
    }

    public static function count(string $where = '1', array $params = []): int
    {
        if (static::$softDelete && $where === '1') {
            $where = 'deleted_at IS NULL';
        } elseif (static::$softDelete) {
            $where = "({$where}) AND deleted_at IS NULL";
        }
        return static::db()->count(static::$table, $where, $params);
    }
}
