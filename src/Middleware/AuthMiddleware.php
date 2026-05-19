<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Models\AuditLog;
use App\Models\User;

class AuthMiddleware
{
    public function handle(Request $request): ?Response
    {
        if (empty($_SESSION['user'])) {
            // Detect auto-logout: cookie was set at login but session no longer exists
            if (!empty($_COOKIE['auth_uid'])) {
                $userId = (int)$_COOKIE['auth_uid'];
                if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                    $ip = trim($_SERVER['HTTP_CLIENT_IP']);
                } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                    $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
                } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
                    $ip = trim($_SERVER['HTTP_X_REAL_IP']);
                } else {
                    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
                }
                if ($ip === '::1') $ip = '127.0.0.1';
                if (str_starts_with($ip, '::ffff:')) $ip = substr($ip, 7);
                $ua     = $_SERVER['HTTP_USER_AGENT'] ?? '';
                AuditLog::log([
                    'user_id'     => $userId,
                    'action_type' => AUDIT_LOGOUT,
                    'module_name' => 'auth',
                    'status'      => 'success',
                    'description' => 'Session expired (auto-logout)',
                    'ip_address'  => $ip,
                    'user_agent'  => $ua,
                ]);
                setcookie('auth_uid', '', time() - 3600, '/', '', false, true);
            }

            if ($request->isApi()) {
                return (new Response())->error('Authentication required', 401);
            }
            return (new Response())->redirect('/login');
        }

        // Refresh permissions from DB on every request so role/permission
        // changes take effect immediately without requiring re-login.
        $userId = $_SESSION['user']['id'] ?? null;
        if ($userId) {
            $permissions             = User::getPermissions((int)$userId);
            $_SESSION['permissions'] = array_map(fn($p) => "{$p['module']}.{$p['action']}", $permissions);
        }

        return null;
    }
}
