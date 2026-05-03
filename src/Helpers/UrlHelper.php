<?php

if (!function_exists('app_base_path')) {
    function app_base_path(): string
    {
        return $_ENV['APP_BASE_PATH'] ?? '';
    }
}

if (!function_exists('app_url')) {
    function app_url(string $path = ''): string
    {
        $basePath = app_base_path();
        $path = '/' . ltrim($path, '/');

        if ($basePath === '' || $basePath === '/') {
            return $path === '//' ? '/' : $path;
        }

        return rtrim($basePath, '/') . $path;
    }
}

if (!function_exists('asset_url')) {
    function asset_url(string $path = ''): string
    {
        return app_url('/' . ltrim($path, '/'));
    }
}
