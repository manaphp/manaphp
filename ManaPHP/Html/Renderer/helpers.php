<?php

if (!function_exists('attr_nv')) {
    /**
     * @param string $name
     * @param string $default
     *
     * @return string
     */
    function attr_nv($name, $default = '')
    {
        return sprintf('name="%s" value="%s"', $name, e(input($name, $default)));
    }
}

if (!function_exists('attr_inv')) {
    /**
     * @param string $name
     * @param string $default
     *
     * @return string
     */
    function attr_inv($name, $default = '')
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
    /**
     *
     * @param array  $files
     * @param string $name
     *
     * @return string
     */
    function bundle($files, $name = 'app')
    {
        return container(\ManaPHP\Html\Renderer\AssetBundleInterface::class)->bundle($files, $name);
    }
}

if (!function_exists('action')) {
    /**
     * @param array|string $args
     * @param bool|string  $scheme
     *
     * @return string
     */
    function action($args = [], $scheme = false)
    {
        return container(\ManaPHP\Http\RouterInterface::class)->createUrl($args, $scheme);
    }
}

if (!function_exists('url')) {
    /**
     * @param string|array $args
     * @param bool|string  $scheme
     *
     * @return string
     */
    function url($args, $scheme = false)
    {
        return container(\ManaPHP\Http\UrlInterface::class)->get($args, $scheme);
    }
}

if (!function_exists('asset')) {
    /**
     * @param string $path
     *
     * @return string
     */
    function asset($path)
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