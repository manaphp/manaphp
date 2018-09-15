<?php

namespace ManaPHP;
use ManaPHP\Di\FactoryDefault;

/**
 * Class ManaPHP\Application
 *
 * @package application
 *
 * @property \ManaPHP\DotenvInterface       $dotenv
 * @property \ManaPHP\ErrorHandlerInterface $errorHandler
 */
class Application extends Component implements ApplicationInterface
{
    /**
     * @var string
     */
    protected $_classFileName;

    /**
     * @var string
     */
    protected $_rootDir;

    /**
     * Application constructor.
     *
     * @param \ManaPHP\Loader $loader
     */
    public function __construct($loader = null)
    {
        $calledClass = get_called_class();
        $this->_classFileName = (new \ReflectionClass($calledClass))->getFileName();

        ini_set('default_socket_timeout', -1);

        $GLOBALS['DI'] = $this->getDi();

        $this->_di->setShared('loader', $loader ?: new Loader());
        $this->_di->setShared('application', $this);

        $rootDir = $this->getRootDir();
        $appDir = $rootDir . '/app';
        $appNamespace = 'App';
        $publicDir = isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : $rootDir . '/public';

        if (strpos($calledClass, 'ManaPHP\\') !== 0) {
            $appDir = dirname($this->_classFileName);
            $appNamespace = substr($calledClass, 0, strrpos($calledClass, '\\'));
            $publicDir = $rootDir . '/public';
        }

        $this->alias->set('@public', $publicDir);
        $this->alias->set('@app', $appDir);
        $this->alias->set('@ns.app', $appNamespace);
        $this->loader->registerNamespaces([$appNamespace => $appDir]);

        $this->alias->set('@views', $appDir . '/Views');

        $this->alias->set('@root', $rootDir);
        $this->alias->set('@data', $rootDir . '/data');
        $this->alias->set('@tmp', $rootDir . '/tmp');
        $this->alias->set('@config', $rootDir . '/config');

        $web = '';
        if (isset($_SERVER['SCRIPT_NAME']) && ($pos = strrpos($_SERVER['SCRIPT_NAME'], '/')) > 0) {
            $web = substr($_SERVER['SCRIPT_NAME'], 0, $pos);
            if (substr_compare($web, '/public', -7) === 0) {
                $web = substr($web, 0, -7);
            }
        }
        $this->alias->set('@web', $web);

        $this->loader->registerFiles('@manaphp/helpers.php');
    }

    /**
     * @return string
     */
    public function getRootDir()
    {
        if (!$this->_rootDir) {
            if (strpos(get_called_class(), 'ManaPHP\\') !== 0) {
                $this->_rootDir = dirname(dirname($this->_classFileName));
            } elseif (isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] === dirname($_SERVER['SCRIPT_FILENAME'])) {
                $this->_rootDir = dirname($_SERVER['DOCUMENT_ROOT']);
            } else {
                $rootDir = realpath(dirname($_SERVER['SCRIPT_FILENAME']));
                if (is_file($rootDir . '/index.php')) {
                    $rootDir = dirname($rootDir);
                }
                $this->_rootDir = $rootDir;
            }
        }

        return $this->_rootDir;
    }

    /**
     * @param string $rootDir
     *
     * @return static
     */
    public function setRootDir($rootDir)
    {
        $this->_rootDir = $rootDir;
        return $this;
    }

    public function getDi()
    {
        if (!$this->_di) {
            $this->_di = new FactoryDefault();
        }
        return $this->_di;
    }

    public function registerServices()
    {
        $configure = $this->configure;

        if ($configure->timezone) {
            date_default_timezone_set($configure->timezone);
        }
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

    public function main()
    {
        if ($this->filesystem->fileExists('@root/.env')) {
            $this->dotenv->load();
        }

        if ($this->filesystem->fileExists('@config/app.php')) {
            $this->configure->load();
        }

        $this->registerServices();
    }
}