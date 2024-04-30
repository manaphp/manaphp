<?php
declare(strict_types=1);

use ManaPHP\Helper\Container;
use ManaPHP\Http\InputInterface;
use ManaPHP\Http\RouterInterface;
use ManaPHP\Http\UrlInterface;
use ManaPHP\Mvc\View\AssetInterface;

if (!function_exists('attr_nv')) {
    function attr_nv(string $name, string $default = ''): string
    {
        $input = Container::get(InputInterface::class);
        return sprintf('name="%s" value="%s"', $name, e($input->get($name, $default)));
    }
}

if (!function_exists('attr_inv')) {
    function attr_inv(string $name, string $default = ''): string
    {
        if ($pos = strpos($name, '[')) {
            $id = substr($name, $pos + 1, -1);
        } else {
            $id = $name;
        }

        $input = Container::get(InputInterface::class);

        return sprintf('id="%s" name="%s" value="%s"', $id, $name, e($input->get($name, $default)));
    }
}

if (!function_exists('action')) {
    function action(string|array $args = [], bool|string $scheme = false): string
    {
        return Container::get(RouterInterface::class)->createUrl($args, $scheme);
    }
}

if (!function_exists('url')) {
    function url(string|array $args, bool|string $scheme = false): string
    {
        return Container::get(UrlInterface::class)->get($args, $scheme);
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return Container::get(AssetInterface::class)->get($path);
    }
}