<?php
declare(strict_types=1);

use ManaPHP\Helper\Container;

if (!function_exists('attr_nv')) {
    function attr_nv(string $name, string $default = ''): string
    {
        return sprintf('name="%s" value="%s"', $name, e(input($name, $default)));
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

        return sprintf('id="%s" name="%s" value="%s"', $id, $name, e(input($name, $default)));
    }
}

if (!function_exists('bundle')) {
    function bundle(array $files, string $name = 'app'): string
    {
        return Container::get(\ManaPHP\Html\Renderer\AssetBundleInterface::class)->bundle($files, $name);
    }
}

if (!function_exists('action')) {
    function action(string|array $args = [], bool|string $scheme = false): string
    {
        return Container::get(\ManaPHP\Http\RouterInterface::class)->createUrl($args, $scheme);
    }
}

if (!function_exists('url')) {
    function url(string|array $args, bool|string $scheme = false): string
    {
        return Container::get(\ManaPHP\Http\UrlInterface::class)->get($args, $scheme);
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return Container::get(\ManaPHP\Mvc\View\AssetInterface::class)->get($path);
    }
}