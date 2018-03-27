<?php

namespace ManaPHP;

use ManaPHP\Application\AbortException;
use ManaPHP\Di\FactoryDefault;

/**
 * Class ManaPHP\Application
 *
 * @package application
 *
 * @property \ManaPHP\Loader              $loader
 * @property \ManaPHP\DebuggerInterface   $debugger
 * @property \ManaPHP\FilesystemInterface $filesystem
 */
abstract class Application extends Component implements ApplicationInterface
{
    /**
     * Application constructor.
     *
     * @param \ManaPHP\Loader      $loader
     * @param \ManaPHP\DiInterface $di
     */
    public function __construct($loader, $di = null)
    {
        $this->_di = $di ?: new FactoryDefault();
        $GLOBALS['DI'] = $this->_di;

        $this->_di->setShared('loader', $loader);
        $this->_di->setShared('application', $this);

        $className = get_called_class();
        /** @noinspection PhpUnhandledExceptionInspection */
        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        $fileName = (new \ReflectionClass($className))->getFileName();

        $app_path = dirname($fileName);
        $app_ns = substr($className, 0, strrpos($className, '\\'));
        $root_path = dirname($app_path);

        $this->loader->registerNamespaces([$app_ns => $app_path]);
        $this->alias->set('@root', $root_path);
        $this->alias->set('@app', $app_path);
        $this->alias->set('@ns.app', $app_ns);
        $this->alias->set('@data', $root_path . '/data');

        $web = '';
        if (isset($_SERVER['SCRIPT_NAME']) && ($pos = strrpos($_SERVER['SCRIPT_NAME'], '/')) > 0) {
            $web = substr($_SERVER['SCRIPT_NAME'], 0, $pos);
            if (substr_compare($web, '/public', -7) === 0) {
                $web = substr($web, 0, -7);
            }
        }
        $this->alias->set('@web', $web);

        $router = $app_ns . '\Router';
        if (class_exists($router)) {
            $this->_di->setShared('router', $router);
        }
    }

    /**
     * @param int    $code
     * @param string $message
     *
     * @throws \ManaPHP\Application\AbortException
     */
    public function abort($code, $message)
    {
        throw new AbortException($message, $code);
    }

    public function registerServices()
    {
        $configure = $this->configure;

        date_default_timezone_set($configure->timezone);
        $this->_di->setShared('crypt', [$configure->master_key]);

        foreach ($configure->aliases as $alias => $path) {
            $this->_di->alias->set($alias, $path);
        }

        foreach ($configure->components as $component => $definition) {
            $this->_di->setShared($component, $definition);
        }

        foreach ($configure->bootstraps as $bootstrap) {
            $this->_di->getShared($bootstrap);
        }
    }
}