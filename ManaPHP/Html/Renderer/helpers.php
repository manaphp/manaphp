<?php
declare(strict_types=1);

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
        return container(\ManaPHP\Html\Renderer\AssetBundleInterface::class)->bundle($files, $name);
    }
}

if (!function_exists('action')) {
    function action(string|array $args = [], bool|string $scheme = false): string
    {
        return container(\ManaPHP\Http\RouterInterface::class)->createUrl($args, $scheme);
    }
}

if (!function_exists('url')) {
    function url(string|array $args, bool|string $scheme = false): string
    {
        return container(\ManaPHP\Http\UrlInterface::class)->get($args, $scheme);
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        static $alias;
        if (!$alias) {
            $alias = container(\ManaPHP\AliasInterface::class);
        }

        static $paths = [];

        if (isset($paths[$path])) {
            return $paths[$path];
        }

        if (!str_contains($path, '?') && is_file($file = $alias->get('@public') . $path)) {
            return $paths[$path] = $alias->get('@asset') . $path . '?' . substr(md5_file($file), 0, 12);
        } else {
            return $paths[$path] = $alias->get('@asset') . $path;
        }
    }
}