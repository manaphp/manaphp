<?php

namespace ManaPHP;

/**
 * Class ManaPHP\Autoloader
 *
 * @package autoloader
 */
class Autoloader
{
    /**
     * @var string
     */
    protected $_dir;

    /**
     * Autoloader constructor.
     *
     * @param string $dir
     */
    public function __construct($dir = null)
    {
        $this->_dir = $dir ?: dirname(__DIR__);

        if (DIRECTORY_SEPARATOR === '\\') {
            $this->_dir = str_replace('\\', '/', $this->_dir);
        }

        $al_function = [$this, '___autoload'];
        spl_autoload_register($al_function);
    }

    /**
     * @param string $className
     *
     * @return bool
     */
    public function ___autoload($className)
    {
        if (strpos($className, 'ManaPHP') !== 0) {
            return false;
        }

        $file = $this->_dir . '/' . str_replace('\\', '/', $className) . '.php';
        if (is_file($file)) {
            if (PHP_EOL !== "\n" && str_replace('\\', '/', realpath($file)) !== $file) {
                trigger_error('File name case mismatch for ' . $file, E_USER_ERROR);
            }

            /** @noinspection PhpIncludeInspection */
            require $file;

            return true;
        }

        return false;
    }
}