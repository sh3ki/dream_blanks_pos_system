<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

abstract class Controller
{
    protected function json(mixed $data, int $status = 200): Response
    {
        return (new Response())->json(['data' => $data], $status);
    }

    protected function success(mixed $data = null, string $message = 'Operation successful', int $code = 200): Response
    {
        return (new Response())->success($data, $message, $code);
    }

    protected function error(string $message, int $code = 400, array $errors = []): Response
    {
        return (new Response())->error($message, $code, $errors);
    }

    protected function view(string $template, array $data = [], int $status = 200): Response
    {
        return (new Response())->view($template, $data, $status);
    }

    protected function redirect(string $url, int $status = 302): Response
    {
        return (new Response())->redirect($url, $status);
    }

    protected function paginate(Request $request): array
    {
        $page    = max(1, (int)$request->query('page', 1));
        $perPage = min(100, max(1, (int)$request->query('per_page', 10)));
        return [$page, $perPage];
    }

    protected function csrfValidate(Request $request): bool
    {
        $token = $request->csrfToken();
        return $token && hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }

    protected function currentUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    protected function currentUserId(): ?int
    {
        return $_SESSION['user']['id'] ?? null;
    }

    protected function hasPermission(string $module, string $action): bool
    {
        $permissions = $_SESSION['permissions'] ?? [];
        return in_array("{$module}.{$action}", $permissions)
            || in_array("{$module}.*", $permissions)
            || in_array('*.*', $permissions);
    }

    protected function requirePermission(string $module, string $action): void
    {
        if (!$this->hasPermission($module, $action)) {
            throw new \App\Exceptions\ForbiddenException("Permission denied: {$module}.{$action}");
        }
    }
}
