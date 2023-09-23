<?php
declare(strict_types=1);

namespace ManaPHP\Helper;

use ManaPHP\Di\InvokerInterface;
use ManaPHP\Di\MakerInterface;
use Psr\Container\ContainerInterface;

class Container
{
    public static function get(string $id): mixed
    {
        /** @var ContainerInterface $container */
        $container = $GLOBALS['Psr\Container\ContainerInterface'];

        return $container->get($id);
    }

    public static function has(string $id): bool
    {
        /** @var ContainerInterface $container */
        $container = $GLOBALS['Psr\Container\ContainerInterface'];

        return $container->has($id);
    }

    public static function make(string $class, array $parameters = []): mixed
    {
        return self::get(MakerInterface::class)->make($class, $parameters);
    }

    public static function call(callable $callable, array $parameters = []): mixed
    {
        return self::get(InvokerInterface::class)->call($callable, $parameters);
    }
}