<?php
declare(strict_types=1);

namespace ManaPHP\Invoking;

use Psr\Container\ContainerInterface;

interface ArgumentResolvable
{
    public static function argumentResolve(ContainerInterface $container): mixed;
}