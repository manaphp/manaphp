<?php

namespace ManaPHP;

use ManaPHP\Application\AbortException;
use ManaPHP\Di\FactoryDefault;

/**
 * Class ManaPHP\Application
 *
 * @package application
 *
 * @property \ManaPHP\DebuggerInterface $debugger
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

        $loader->alias = $this->alias;

        $this->_di->setShared('loader', $loader);
        $this->_di->setShared('application', $this);

        if ($appDir = $this->alias->has('@app')) {
            $rootDir = dirname($appDir);
            $this->loader->registerNamespaces([$this->alias->resolveNS('@ns.app') => $appDir]);
        } else {
            $rootDir = null;
            $appDir = null;
            $appNamespace = null;

            $calledClass = get_called_class();
            if (strpos($calledClass, 'ManaPHP\\') !== 0) {
                $calledFile = (new \ReflectionClass($calledClass))->getFileName();

                $appDir = dirname($calledFile);
                $rootDir = dirname($appDir);
                $appNamespace = substr($calledClass, 0, strrpos($calledClass, '\\'));
            } else {
                $entryPointDir = dirname(get_included_files()[0]);
                if (is_dir($entryPointDir . '/app')) {
                    $rootDir = $entryPointDir;
                    $appDir = $rootDir . '/app';
                } elseif ($pos = strrpos($entryPointDir, DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR)) {
                    $rootDir = substr($entryPointDir, 0, $pos);
                    $appDir = $rootDir . '/app';
                }
            }

            if ($appDir) {
                $this->alias->set('@app', $appDir);
            }

            if ($appNamespace) {
                $this->alias->set('@ns.app', $appNamespace);
                $this->loader->registerNamespaces([$appNamespace => $appDir]);
            }
        }

        if ($rootDir) {
            $this->alias->set('@root', $rootDir);
            $this->alias->set('@data', $rootDir . '/data');
            $this->alias->set('@tmp', $rootDir . '/tmp');
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