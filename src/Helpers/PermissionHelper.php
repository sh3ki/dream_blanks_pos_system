<?php

if (!function_exists('can')) {
    /**
     * Check if the current user has a given permission.
     * Usage: can('products', 'add')
     */
    function can(string $module, string $action): bool
    {
        $permissions = $_SESSION['permissions'] ?? [];
        return in_array("{$module}.{$action}", $permissions)
            || in_array("{$module}.*", $permissions)
            || in_array('*.*', $permissions);
    }
}
