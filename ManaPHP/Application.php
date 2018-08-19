<?php

namespace ManaPHP;

use ManaPHP\Application\AbortException;

/**
 * Class ManaPHP\Application
 *
 * @package application
 *
 * @property \ManaPHP\DotenvInterface       $dotenv
 * @property \ManaPHP\ErrorHandlerInterface $errorHandler
 */
abstract class Application extends Component implements ApplicationInterface
{
    /**
     * Application constructor.
     *
     * @param \ManaPHP\Loader $loader
     */
    public function __construct($loader)
    {
        ini_set('default_socket_timeout', -1);

        $GLOBALS['DI'] = $this->getDi();

        $this->_di->setShared('loader', $loader);
        $this->_di->setShared('application', $this);

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
            $entryPointDir = dirname($_SERVER['SCRIPT_FILENAME']);

            $rootDir = is_dir($entryPointDir . '/app') ? $entryPointDir : dirname($entryPointDir);
            $appDir = $rootDir . '/app';
            $appNamespace = 'App';
        }

        $this->alias->set('@app', $appDir);
        $this->alias->set('@ns.app', $appNamespace);
        $this->loader->registerNamespaces([$appNamespace => $appDir]);

        $this->alias->set('@views', $appDir . '/Views');

        $this->alias->set('@root', $rootDir);
        $this->alias->set('@public', $rootDir . '/public');
        $this->alias->set('@data', $rootDir . '/data');
        $this->alias->set('@tmp', $rootDir . '/tmp');
        $this->alias->set('@config', $rootDir . '/config');

        $this->loader->registerFiles('@manaphp/helpers.php');
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

        if ($configure->traces) {
            $this->_di->setTraces($configure->traces);
        }

        foreach ($configure->components as $component => $definition) {
            if ($definition === null) {
                $this->_di->remove($component);
            } else {
                $this->_di->setShared($component, $definition);
            }
        }

        foreach ($configure->bootstraps as $bootstrap) {
            if ($bootstrap) {
                $this->_di->getShared($bootstrap);
            }
        }
    }

    /**
     * @param \Exception|\Error $exception
     */
    public function handleException($exception)
    {
        $this->errorHandler->handle($exception);
    }
}