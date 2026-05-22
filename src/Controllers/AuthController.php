<?php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;
use App\Exceptions\AuthException;
use App\Exceptions\ValidationException;

class AuthController extends Controller
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function showLogin(Request $request): Response
    {
        return $this->view('auth/login', ['title' => 'Login | Dream Blanks POS']);
    }

    public function login(Request $request): Response
    {
        try {
            $user = $this->authService->login(
                $request->input('username_or_email', ''),
                $request->input('password', ''),
                $request->ip(),
                $request->userAgent()
            );

            if ($request->isApi()) {
                return $this->success($user, 'Login successful');
            }
            return $this->redirect('/pos');

        } catch (AuthException $e) {
            if ($request->isApi()) {
                return $this->error($e->getMessage(), $e->getCode());
            }
            return $this->view('auth/login', ['error' => $e->getMessage(), 'title' => 'Login | Dream Blanks POS']);
        }
    }

    public function logout(Request $request): Response
    {
        $userId = $this->currentUserId();
        if ($userId) {
            $this->authService->logout($userId, $request->ip(), $request->userAgent());
        }

        if ($request->isApi()) {
            return $this->success(null, 'Logout successful');
        }
        return $this->redirect('/login');
    }

    public function showForgotPassword(Request $request): Response
    {
        return $this->view('auth/forgot-password', ['title' => 'Forgot Password | Dream Blanks POS']);
    }

    public function forgotPassword(Request $request): Response
    {
        try {
            $email = $request->input('email', '');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new ValidationException(['email' => ['Please enter a valid email address']]);
            }

            $this->authService->forgotPassword($email);

            if ($request->isApi()) {
                return $this->success(null, 'OTP sent to your email');
            }
            return $this->view('auth/forgot-password', [
                'success' => 'If that email exists, an OTP has been sent.',
                'title'   => 'Forgot Password | Dream Blanks POS'
            ]);

        } catch (ValidationException $e) {
            if ($request->isApi()) {
                return $this->error($e->getMessage(), 422, $e->getErrors());
            }
            return $this->view('auth/forgot-password', ['errors' => $e->getErrors(), 'title' => 'Forgot Password | Dream Blanks POS']);
        }
    }

    public function verifyOtp(Request $request): Response
    {
        try {
            $email = $request->input('email', '');
            $otp   = $request->input('otp', '');
            $token = $this->authService->verifyOtp($email, $otp);

            if ($request->isApi()) {
                return $this->success(['reset_token' => $token], 'OTP verified');
            }
            return $this->redirect('/reset-password?token=' . $token);

        } catch (ValidationException $e) {
            if ($request->isApi()) {
                return $this->error($e->getMessage(), 422, $e->getErrors());
            }
            return $this->view('auth/forgot-password', ['errors' => $e->getErrors(), 'title' => 'Forgot Password | Dream Blanks POS']);
        }
    }

    public function showResetPassword(Request $request): Response
    {
        return $this->view('auth/reset-password', ['title' => 'Reset Password | Dream Blanks POS']);
    }

    public function resetPassword(Request $request): Response
    {
        try {
            $token       = $request->input('reset_token', '');
            $newPassword = $request->input('new_password', '');

            $this->authService->resetPassword($token, $newPassword);

            if ($request->isApi()) {
                return $this->success(null, 'Password reset successful');
            }
            return $this->view('auth/login', ['success' => 'Password reset! Please log in.', 'title' => 'Login | Dream Blanks POS']);

        } catch (\App\Exceptions\AuthException | ValidationException $e) {
            if ($request->isApi()) {
                return $this->error($e->getMessage(), $e->getCode(), method_exists($e, 'getErrors') ? $e->getErrors() : []);
            }
            return $this->view('auth/reset-password', ['error' => $e->getMessage(), 'title' => 'Reset Password | Dream Blanks POS']);
        }
    }
}
