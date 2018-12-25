<?php

namespace Skiphog;

/**
 * Simple container
 * Class Container
 *
 * @package Skiphog
 */
class Container
{
    /**
     * Collection of stored bindings
     *
     * @var array $definitions
     */
    protected static $definitions = [];

    /**
     * Collection of stored instances
     *
     * @var array $registry
     */
    protected static $registry = [];

    /**
     * Resolve a service instance from the container.
     *
     * @param string $name
     *
     * @return object|mixed
     * @throws \Throwable
     */
    public static function get($name)
    {
        if (array_key_exists($name, self::$registry)) {
            return self::$registry[$name];
        }

        if (array_key_exists($name, self::$definitions)) {
            return self::$registry[$name] = self::$definitions[$name] instanceof \Closure ?
                call_user_func(self::$definitions[$name]) : self::$definitions[$name];
        }

        return static::autoResolve($name);
    }

    /**
     * Bind a new instance construction blueprint within the container
     *
     * @param string $name
     * @param mixed  $value
     */
    public static function set($name, $value): void
    {
        if (array_key_exists($name, self::$registry)) {
            unset(self::$registry[$name]);
        }

        self::$definitions[$name] = $value;
    }

    /**
     * Attempt to auto resolve the dependency chain.
     *
     * @param string $name
     *
     * @return bool|object
     * @throws \Throwable
     */
    protected static function autoResolve($name)
    {
        if (!class_exists($name)) {
            throw new ContainerException("Unknown service [ {$name} ]");
        }

        $reflectionClass = new \ReflectionClass($name);

        if (!$reflectionClass->isInstantiable()) {
            throw new ContainerException("Unable to instance [ {$name} ]");
        }

        if (!$constructor = $reflectionClass->getConstructor()) {
            return new $name;
        }

        try {
            $args = array_map(function (\ReflectionParameter $param) {
                return static::get($param->getClass()->getName());
            }, $constructor->getParameters());
        } catch (\Throwable $e) {
            throw new ContainerException("Unable to resolve complex dependencies [ {$name} ]");
        }

        return $reflectionClass->newInstanceArgs($args);
    }
}
