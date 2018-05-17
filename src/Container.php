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
     * @throws \Exception
     */
    public static function get($name)
    {
        if (array_key_exists($name, self::$registry)) {
            return self::$registry[$name];
        }

        if (array_key_exists($name, self::$definitions)) {
            $item = self::$definitions[$name];

            return self::$registry[$name] = $item instanceof \Closure ? $item() : $item;
        }

        if ($instance = static::autoResolve($name)) {
            return self::$registry[$name] = $instance;
        }

        throw new \InvalidArgumentException('Unknown service [ ' . $name . ' ]');
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
     * @throws \Exception
     */
    protected static function autoResolve($name)
    {
        if (!class_exists($name)) {
            return false;
        }

        $reflectionClass = new \ReflectionClass($name);

        if (!$reflectionClass->isInstantiable()) {
            throw new \InvalidArgumentException('Unable to instance [ ' . $name . ' ]');
        }

        if (!$constructor = $reflectionClass->getConstructor()) {
            return new $name;
        }

        try {
            $args = array_map(function (\ReflectionParameter $param) {
                return static::get($param->getClass()->getName());
            }, $constructor->getParameters());
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Unable to resolve complex dependencies [ ' . $name . ' ]');
        }

        return $reflectionClass->newInstanceArgs($args);
    }
}
