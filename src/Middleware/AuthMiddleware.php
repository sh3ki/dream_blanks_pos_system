<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

class AuthMiddleware
{
    public function handle(Request $request): ?Response
    {
        if (empty($_SESSION['user'])) {
            if ($request->isApi()) {
                return (new Response())->error('Authentication required', 401);
            }
            return (new Response())->redirect('/login');
        }

        return null;
    }
}
