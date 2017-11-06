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
     * @var array
     */
    protected $_modules;

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
        $app_ns = basename($app_path);
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
     * @return array
     */
    public function getModules()
    {
        if ($this->_modules === null) {
            $modules = [];
            foreach (glob($this->alias->resolve('@app/*'), GLOB_ONLYDIR) as $dir) {
                if (is_dir($dir . '/Controllers')) {
                    $module = basename($dir);
                    if ($module !== 'Cli') {
                        $modules[] = $module;
                    }
                }
            }

            $this->_modules = $modules;
        }

        return $this->_modules;
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

        if ($configure->master_key !== '') {
            $this->crypt->setMasterKey($configure->master_key);
        }

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