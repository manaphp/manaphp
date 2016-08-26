<?php

namespace ManaPHP;

/**
 * ManaPHP\Loader
 *
 * This component helps to load your project classes automatically based on some conventions
 *
 */
class Loader
{
    /**
     * @var array
     */
    protected $_classes = [];

    /**
     * @var array
     */
    protected $_namespaces = [];

    public function __construct()
    {
        $al_function = [$this, '___autoload'];
        spl_autoload_register($al_function);
    }

    /**
     * Register namespaces and their related directories
     *
     * <code>
     * $loader->registerNamespaces(array(
     *        ’Example\\Base’ => ’vendor/example/base/’,
     *        ’Example\\Adapter’ => ’vendor/example/adapter/’,
     *        ’Example’ => ’vendor/example/’
     *        ));
     * </code>
     *
     * @param array   $namespaces
     * @param boolean $merge
     *
     * @return static
     */
    public function registerNamespaces($namespaces, $merge = true)
    {
        foreach ($namespaces as $namespace => $path) {
            $path = rtrim($path, '\\/');
            if (DIRECTORY_SEPARATOR === '\\') {
                /** @noinspection AlterInForeachInspection */
                $namespaces[$namespace] = str_replace('\\', '/', $path);
            }
        }

        $this->_namespaces = $merge ? array_merge($this->_namespaces, $namespaces) : $namespaces;

        $cmp_function = function ($a, $b) {
            return strlen($b) - strlen($a);
        };

        uksort($this->_namespaces, $cmp_function);

        return $this;
    }

    /**
     * Register classes and their locations
     *
     * @param array   $classes
     * @param boolean $merge
     *
     * @return static
     */
    public function registerClasses($classes, $merge = true)
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            foreach ($classes as $key => $path) {
                /** @noinspection AlterInForeachInspection */
                $classes[$key] = str_replace('\\', '/', $path);
            }
        }

        $this->_classes = $merge ? array_merge($this->_classes, $classes) : $classes;

        return $this;
    }

    /**
     * If a file exists, require it from the file system.
     *
     * @param string $file The file to require.
     *
     * @return true
     */
    protected function ___requireFile($file)
    {
        if (PHP_EOL !== "\n") {
            $realPath = str_replace('\\', '/', realpath($file));
            if ($realPath !== $file) {
                trigger_error("File name ($realPath) case mismatch for .$file", E_USER_ERROR);
            }
        }
        /** @noinspection PhpIncludeInspection */
        require $file;

        return true;
    }

    /**
     * Makes the work of autoload registered classes
     *
     * @param string $className
     *
     * @return bool
     */
    public function ___autoload($className)
    {
        if (isset($this->_classes[$className])) {
            return $this->___requireFile($this->_classes[$className]);
        }

        /** @noinspection LoopWhichDoesNotLoopInspection */
        foreach ($this->_namespaces as $namespace => $path) {
            if (strpos($className, $namespace) !== 0) {
                continue;
            }

            $file = $path . str_replace('\\', '/', substr($className, strlen($namespace))) . '.php';

            return $this->___requireFile($file);
        }

        return false;
    }
}
