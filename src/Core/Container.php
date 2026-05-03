<?php

namespace App\Core;

class Container
{
    private static array $bindings  = [];
    private static array $instances = [];

    public static function bind(string $abstract, callable $factory): void
    {
        self::$bindings[$abstract] = $factory;
    }

    public static function singleton(string $abstract, callable $factory): void
    {
        self::$bindings[$abstract] = function () use ($abstract, $factory) {
            if (!isset(self::$instances[$abstract])) {
                self::$instances[$abstract] = $factory();
            }
            return self::$instances[$abstract];
        };
    }

    public static function make(string $abstract): mixed
    {
        if (isset(self::$bindings[$abstract])) {
            return (self::$bindings[$abstract])();
        }

        // Auto-resolve if class exists
        if (class_exists($abstract)) {
            return new $abstract();
        }

        throw new \RuntimeException("Unable to resolve: {$abstract}");
    }

    public static function instance(string $abstract, mixed $instance): void
    {
        self::$instances[$abstract] = $instance;
        self::$bindings[$abstract]  = fn() => $instance;
    }
}
