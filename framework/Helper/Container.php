<?php
declare(strict_types=1);

namespace ManaPHP\Helper;

use ManaPHP\Di\ContainerInterface;

class Container
{
    public static function get(string $id): mixed
    {
        /** @var ContainerInterface $container */
        $container = $GLOBALS['ManaPHP\Di\ContainerInterface'];

        return $container->get($id);
    }

    public static function has(string $id): bool
    {
        /** @var ContainerInterface $container */
        $container = $GLOBALS['ManaPHP\Di\ContainerInterface'];

        return $container->has($id);
    }

    public static function make(string $class, array $parameters = []): mixed
    {
        /** @var ContainerInterface $container */
        $container = $GLOBALS['ManaPHP\Di\ContainerInterface'];

        return $container->make($class, $parameters);
    }

    public static function call(callable $callable, array $parameters = []): mixed
    {
        /** @var ContainerInterface $container */
        $container = $GLOBALS['ManaPHP\Di\ContainerInterface'];

        return $container->call($callable, $parameters);
    }
}