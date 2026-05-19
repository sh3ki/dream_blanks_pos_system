<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Models\AuditLog;

class AuthMiddleware
{
    public function handle(Request $request): ?Response
    {
        if (empty($_SESSION['user'])) {
            // Detect auto-logout: cookie was set at login but session no longer exists
            if (!empty($_COOKIE['auth_uid'])) {
                $userId = (int)$_COOKIE['auth_uid'];
                $ip     = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
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

        return null;
    }
}
