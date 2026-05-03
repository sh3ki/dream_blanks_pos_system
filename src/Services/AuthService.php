<?php

namespace App\Services;

use App\Models\User;
use App\Models\AuditLog;
use App\Exceptions\AuthException;
use App\Exceptions\ValidationException;

class AuthService
{
    public function login(string $usernameOrEmail, string $password, string $ip, string $userAgent): array
    {
        $user = User::findByEmailOrUsername($usernameOrEmail);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            AuditLog::log([
                'user_id'     => $user['id'] ?? null,
                'action_type' => AUDIT_LOGIN,
                'module_name' => 'auth',
                'status'      => 'failed',
                'description' => "Failed login attempt for: {$usernameOrEmail}",
                'ip_address'  => $ip,
                'user_agent'  => $userAgent,
            ]);
            throw new AuthException('Invalid credentials', 401);
        }

        if ($user['status'] !== STATUS_ACTIVE) {
            throw new AuthException('Account is inactive. Please contact administrator.', 403);
        }

        User::updateLastLogin($user['id']);

        $roles       = User::getRoles($user['id']);
        $permissions = User::getPermissions($user['id']);
        $permList    = array_map(fn($p) => "{$p['module']}.{$p['action']}", $permissions);

        $sessionUser = [
            'id'            => $user['id'],
            'username'      => $user['username'],
            'email'         => $user['email'],
            'first_name'    => $user['first_name'],
            'last_name'     => $user['last_name'],
            'profile_image' => $user['profile_image'] ?? null,
            'roles'         => array_column($roles, 'name'),
        ];

        $_SESSION['user']        = $sessionUser;
        $_SESSION['permissions'] = $permList;
        $_SESSION['csrf_token']  = bin2hex(random_bytes(32));

        AuditLog::log([
            'user_id'     => $user['id'],
            'action_type' => AUDIT_LOGIN,
            'module_name' => 'auth',
            'status'      => 'success',
            'description' => "User logged in",
            'ip_address'  => $ip,
            'user_agent'  => $userAgent,
        ]);

        return $sessionUser;
    }

    public function logout(int $userId, string $ip, string $userAgent): void
    {
        AuditLog::log([
            'user_id'     => $userId,
            'action_type' => AUDIT_LOGOUT,
            'module_name' => 'auth',
            'status'      => 'success',
            'description' => 'User logged out',
            'ip_address'  => $ip,
            'user_agent'  => $userAgent,
        ]);

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }

    public function forgotPassword(string $email): void
    {
        $user = User::findByEmail($email);
        if (!$user) {
            // Silent fail to avoid user enumeration
            return;
        }

        $otp = (string)random_int(100000, 999999);
        User::storeOtp($email, $otp);

        // Send email
        (new EmailService())->sendOtp($email, $otp, $user['first_name']);
    }

    public function verifyOtp(string $email, string $otp): string
    {
        if (!User::verifyOtp($email, $otp)) {
            throw new ValidationException(['otp' => ['Invalid or expired OTP']]);
        }

        $token = bin2hex(random_bytes(32));
        $_SESSION['reset_token'] = $token;
        $_SESSION['reset_email'] = $email;

        return $token;
    }

    public function resetPassword(string $token, string $newPassword): void
    {
        if (empty($_SESSION['reset_token']) || !hash_equals($_SESSION['reset_token'], $token)) {
            throw new AuthException('Invalid reset token');
        }

        $email = $_SESSION['reset_email'] ?? '';
        $user  = User::findByEmail($email);
        if (!$user) {
            throw new AuthException('User not found');
        }

        if (strlen($newPassword) < 8) {
            throw new ValidationException(['new_password' => ['Password must be at least 8 characters']]);
        }

        User::update($user['id'], ['password_hash' => password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12])]);
        User::clearOtp($email);

        unset($_SESSION['reset_token'], $_SESSION['reset_email']);
    }
}
