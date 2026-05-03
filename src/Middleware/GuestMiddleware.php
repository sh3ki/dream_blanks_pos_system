<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

class GuestMiddleware
{
    public function handle(Request $request): ?Response
    {
        if (!empty($_SESSION['user'])) {
            return (new Response())->redirect('/dashboard');
        }
        return null;
    }
}
