<?php

namespace App\Core;

class Request
{
    private array $params = [];
    private array $body;
    private array $query;
    private array $files;
    private array $headers;

    public function __construct()
    {
        $this->body    = $_POST;
        $this->query   = $_GET;
        $this->files   = $_FILES;
        $this->headers = getallheaders() ?: [];

        // Parse JSON body for API requests
        $contentType = $this->header('Content-Type') ?? '';
        if (str_contains($contentType, 'application/json')) {
            $json = file_get_contents('php://input');
            if ($json) {
                $decoded = json_decode($json, true);
                if (is_array($decoded)) {
                    $this->body = $decoded;
                }
            }
        }
    }

    public function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function uri(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

        // Support apps hosted in a subfolder (e.g., /dream_blanks_pos_system)
        $basePath = $_ENV['APP_BASE_PATH'] ?? '';
        if ($basePath === '') {
            $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
            $basePath = rtrim(str_replace('/public', '', $scriptDir), '/');
        }

        if ($basePath !== '' && $basePath !== '/' && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }

        return rtrim($uri, '/') ?: '/';
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function only(array $keys): array
    {
        return array_intersect_key($this->all(), array_flip($keys));
    }

    public function has(string $key): bool
    {
        return isset($this->body[$key]) || isset($this->query[$key]);
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function header(string $key): ?string
    {
        $normalized = str_replace('-', '_', strtoupper($key));
        return $_SERVER['HTTP_' . $normalized]
            ?? $this->headers[$key]
            ?? null;
    }

    public function isAjax(): bool
    {
        return $this->header('X-Requested-With') === 'XMLHttpRequest'
            || str_contains($this->header('Accept') ?? '', 'application/json');
    }

    public function isApi(): bool
    {
        return str_starts_with($this->uri(), '/api/');
    }

    public function ip(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '0.0.0.0';
    }

    public function userAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function params(): array
    {
        return $this->params;
    }

    public function csrfToken(): ?string
    {
        return $this->input('_csrf_token')
            ?? $this->header('X-CSRF-Token');
    }
}
