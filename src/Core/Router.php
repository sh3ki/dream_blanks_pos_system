<?php

namespace App\Core;

use App\Exceptions\NotFoundException;

class Router
{
    private array $routes = [];
    private array $middlewares = [];

    public function get(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function put(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }

    public function delete(string $path, array|callable $handler, array $middleware = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    private function addRoute(string $method, string $path, array|callable $handler, array $middleware): void
    {
        $this->routes[] = [
            'method'     => $method,
            'path'       => $path,
            'pattern'    => $this->pathToRegex($path),
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
    }

    private function pathToRegex(string $path): string
    {
        $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $uri    = $request->uri();

        // Support PUT/DELETE via POST _method override
        if ($method === 'POST' && $request->input('_method')) {
            $method = strtoupper($request->input('_method'));
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;

            if (preg_match($route['pattern'], $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $request->setParams($params);

                // Run middleware
                foreach ($route['middleware'] as $middlewareClass) {
                    $mw = new $middlewareClass();
                    $result = $mw->handle($request);
                    if ($result instanceof Response) {
                        return $result;
                    }
                }

                return $this->callHandler($route['handler'], $request);
            }
        }

        throw new NotFoundException("Route not found: {$method} {$uri}");
    }

    private function callHandler(array|callable $handler, Request $request): Response
    {
        if (is_callable($handler)) {
            return call_user_func($handler, $request);
        }

        [$controllerClass, $method] = $handler;
        $controller = new $controllerClass();
        return $controller->$method($request);
    }
}
