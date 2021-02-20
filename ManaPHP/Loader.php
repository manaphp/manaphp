<?php

namespace ManaPHP;

use ManaPHP\Di\Container;

class Loader
{
    /**
     * @var array
     */
    protected $classes = [];

    /**
     * @var array
     */
    protected $namespaces = [];

    /**
     * @var array
     */
    protected $files = [];

    /**
     */
    public function __construct()
    {
        $this->namespaces['ManaPHP'] = DIRECTORY_SEPARATOR === '\\' ? strtr(__DIR__, '\\', '/') : __DIR__;
        spl_autoload_register([$this, 'load'], true, true);

        $this->registerFiles(__DIR__ . '/helpers.php');
    }

    /**
     * Register namespaces and their related directories
     *
     * @param array $namespaces
     * @param bool  $merge
     *
     * @return static
     */
    public function registerNamespaces($namespaces, $merge = true)
    {
        foreach ($namespaces as $namespace => $path) {
            $path = rtrim($path, '\\/');
            if (DIRECTORY_SEPARATOR === '\\') {
                $namespaces[$namespace] = strtr($path, '\\', '/');
            }
        }

        $this->namespaces = $merge ? array_merge($this->namespaces, $namespaces) : $namespaces;

        return $this;
    }

    /**
     * @return array
     */
    public function getRegisteredNamespaces()
    {
        return $this->namespaces;
    }

    /**
     * Register classes and their locations
     *
     * @param array $classes
     * @param bool  $merge
     *
     * @return static
     */
    public function registerClasses($classes, $merge = true)
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            foreach ($classes as $key => $path) {
                $classes[$key] = strtr($path, '\\', '/');
            }
        }

        $this->classes = $merge ? array_merge($this->classes, $classes) : $classes;

        return $this;
    }

    /**
     * @param string|array $files
     *
     * @return static
     */
    public function registerFiles($files)
    {
        foreach ((array)$files as $file) {
            if (isset($this->files[$file])) {
                continue;
            }

            if ($file[0] === '@') {
                $file = Container::getDefault()->getShared('alias')->resolve($file);
            }
            $this->files[$file] = 1;

            /** @noinspection PhpIncludeInspection */
            require $file;
        }

        return $this;
    }

    /**
     * If a file exists, require it from the file system.
     *
     * @param string $file The file to require.
     *
     * @return void
     */
    public function requireFile($file)
    {
        if (PHP_EOL !== "\n" && strpos($file, 'phar://') !== 0) {
            $realPath = strtr(realpath($file), '\\', '/');
            if ($realPath !== $file) {
                trigger_error("File name ($realPath) case mismatch for .$file", E_USER_ERROR);
            }
        }
        /** @noinspection PhpIncludeInspection */
        require $file;
    }

    /**
     * Makes the work of autoload registered classes
     *
     * @param string $className
     *
     * @return bool
     */
    public function load($className)
    {
        if (isset($this->classes[$className])) {
            $file = $this->classes[$className];
            if (!is_file($file)) {
                $error = sprintf('load `%s` class failed: `%s` is not exists.', $className, $file);
                trigger_error($error, E_USER_ERROR);
            }

            //either linux or phar://
            if (PHP_EOL === "\n" || $file[0] === 'p') {
                /** @noinspection PhpIncludeInspection */
                require $file;
            } else {
                $this->requireFile($file);
            }

            return true;
        }

        foreach ($this->namespaces as $namespace => $path) {
            if (strpos($className, $namespace) !== 0) {
                continue;
            }

            $file = $path . strtr(substr($className, strlen($namespace)), '\\', '/') . '.php';
            if (is_file($file)) {
                //either linux or phar://
                if (PHP_EOL === "\n" || $file[0] === 'p') {
                    /** @noinspection PhpIncludeInspection */
                    require $file;
                } else {
                    $this->requireFile($file);
                }

                return true;
            }
        }

        return false;
    }

    /**
     * @return array
     */
    public function dump()
    {
        return get_object_vars($this);
    }
}
