<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

class PermissionMiddleware
{
    private string $module;
    private string $action;

    public function __construct(string $module, string $action)
    {
        $this->module = $module;
        $this->action = $action;
    }

    public function handle(Request $request): ?Response
    {
        $permissions = $_SESSION['permissions'] ?? [];

        $allowed = in_array("{$this->module}.{$this->action}", $permissions)
            || in_array("{$this->module}.*", $permissions)
            || in_array('*.*', $permissions);

        if (!$allowed) {
            if ($request->isApi()) {
                return (new Response())->error('Permission denied', 403);
            }
            return (new Response())->view('errors/403', [], 403);
        }

        return null;
    }
}
