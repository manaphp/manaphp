<?php

namespace ManaPHP;

use ManaPHP\Application\AbortException;
use ManaPHP\Application\Exception as ApplicationException;
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
     * @param \ManaPHP\DiInterface $dependencyInjector
     *
     * @throws \ManaPHP\Application\Exception
     */
    public function __construct($loader, $dependencyInjector = null)
    {
        $this->_dependencyInjector = $dependencyInjector ?: new FactoryDefault();

        $this->_dependencyInjector->setShared('loader', $loader);
        $this->_dependencyInjector->setShared('application', $this);

        $app_path = $this->getAppPath();
        $app_ns = dirname(get_called_class());
        $root_path = dirname(dirname($app_path));

        $this->loader->registerNamespaces([$app_ns => $app_path]);
        $this->alias->set('@root', $root_path);
        $this->alias->set('@app', $app_path);
        $this->alias->set('@ns.app', $app_ns);

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
            $this->_dependencyInjector->setShared('router', $router);
        }
    }

    /**
     * @return string
     * @throws \ManaPHP\Application\Exception
     */
    public function getAppPath()
    {
        $className = get_called_class();
        $included_files = get_included_files();
        $tested_file = (DIRECTORY_SEPARATOR === '\\' ? $className : strtr($className, '\\', '/')) . '.php';
        foreach ($included_files as $file) {
            if (strpos($file, $tested_file) !== false) {
                return dirname($file);
            }
        }

        $dir = dirname($included_files[0]);
        for ($i = 0; $i < 2; $i++) {
            if (is_dir($dir . '/Application')) {
                return $dir . '/Application';
            }
            $dir = dirname($dir);
        }

        throw new ApplicationException('infer appPath failed');
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
        $this->_dependencyInjector->setShared('crypt', [$configure->master_key]);

        foreach ($configure->aliases as $alias => $path) {
            $this->_dependencyInjector->alias->set($alias, $path);
        }

        foreach ($configure->components as $component => $definition) {
            $this->_dependencyInjector->setShared($component, $definition);
        }

        foreach ($configure->bootstraps as $bootstrap) {
            $this->_dependencyInjector->getShared($bootstrap);
        }
    }
}