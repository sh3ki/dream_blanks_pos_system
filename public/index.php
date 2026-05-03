<?php

declare(strict_types=1);

// Bootstrap
define('ROOT_PATH',   dirname(__DIR__));
define('SRC_PATH',    ROOT_PATH . '/src');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('UPLOAD_PATH', PUBLIC_PATH . '/uploads');
define('LOG_PATH',    ROOT_PATH . '/logs');
define('VIEW_PATH',   SRC_PATH . '/Views');
define('DB_PATH',     ROOT_PATH . '/database');

// Load environment
$envFile = ROOT_PATH . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
        }
    }
}

$appUrlPath = parse_url($_ENV['APP_URL'] ?? '', PHP_URL_PATH) ?: '';
if ($appUrlPath === '/' || $appUrlPath === '\\') {
    $appUrlPath = '';
}
$_ENV['APP_BASE_PATH'] = rtrim($appUrlPath, '/');

// Load constants
require_once CONFIG_PATH . '/constants.php';
require_once SRC_PATH . '/Helpers/UrlHelper.php';
require_once SRC_PATH . '/Helpers/IconHelper.php';

// PSR-4 autoloader
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $base   = SRC_PATH . '/';

    if (!str_starts_with($class, $prefix)) return;

    $relative = substr($class, strlen($prefix));
    $file     = $base . str_replace('\\', '/', $relative) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// Error handling
$debug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);
error_reporting($debug ? E_ALL : E_ERROR | E_WARNING | E_PARSE);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', LOG_PATH . '/error.log');

// Timezone
date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'Asia/Manila');

// Session
$appConfig = require CONFIG_PATH . '/app.php';
session_name($appConfig['session']['name']);
session_set_cookie_params([
    'lifetime' => $appConfig['session']['lifetime'] * 60,
    'path'     => '/',
    'secure'   => isset($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// Bootstrap complete — dispatch request
use App\Core\Request;
use App\Core\Response;
use App\Exceptions\NotFoundException;
use App\Exceptions\AuthException;
use App\Exceptions\ForbiddenException;
use App\Exceptions\ValidationException;

$request = new Request();
$router  = require CONFIG_PATH . '/routes.php';

try {
    $response = $router->dispatch($request);
    $response->send();
} catch (NotFoundException $e) {
    if ($request->isApi()) {
        (new Response())->error($e->getMessage(), 404)->send();
    } else {
        (new Response())->view('errors/404', ['message' => $e->getMessage()], 404)->send();
    }
} catch (AuthException $e) {
    if ($request->isApi()) {
        (new Response())->error($e->getMessage(), $e->getCode())->send();
    } else {
        (new Response())->redirect('/login')->send();
    }
} catch (ForbiddenException $e) {
    if ($request->isApi()) {
        (new Response())->error($e->getMessage(), 403)->send();
    } else {
        (new Response())->view('errors/403', [], 403)->send();
    }
} catch (ValidationException $e) {
    (new Response())->error($e->getMessage(), 422, $e->getErrors())->send();
} catch (\Throwable $e) {
    error_log($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    if ($request->isApi()) {
        $message = $debug ? $e->getMessage() : 'Internal server error';
        (new Response())->error($message, 500)->send();
    } else {
        (new Response())->view('errors/500', ['message' => $debug ? $e->getMessage() : 'Internal server error'], 500)->send();
    }
}
