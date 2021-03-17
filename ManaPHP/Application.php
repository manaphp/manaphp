<?php

namespace ManaPHP;

use ManaPHP\Helper\LocalFS;
use ReflectionClass;

/**
 * @property-read \ManaPHP\Configuration\Configure       $configure
 * @property-read \ManaPHP\AliasInterface                $alias
 * @property-read \ManaPHP\LoaderInterface               $loader
 * @property-read \ManaPHP\Configuration\DotenvInterface $dotenv
 * @property-read \ManaPHP\ErrorHandlerInterface         $errorHandler
 * @property-read \ManaPHP\Cli\RunnerInterface           $cliRunner
 */
class Application extends Component implements ApplicationInterface
{
    /**
     * @var string
     */
    protected $class_file;

    /**
     * @param \ManaPHP\Loader $loader
     */
    public function __construct($loader = null)
    {
        if ($loader === null) {
            $loader = new Loader();
        }

        if (!defined('MANAPHP_CLI')) {
            define('MANAPHP_CLI', basename($_SERVER['SCRIPT_FILENAME']) === 'manacli.php');
        }

        $class = static::class;
        $this->class_file = (new ReflectionClass($class))->getFileName();

        ini_set('html_errors', 'off');
        ini_set('default_socket_timeout', -1);

        $factory = $this->getFactory();
        $GLOBALS['CONTAINER'] = $this->container = new $factory();

        if (!defined('MANAPHP_COROUTINE_ENABLED')) {
            define(
                'MANAPHP_COROUTINE_ENABLED', PHP_SAPI === 'cli'
                && extension_loaded('swoole')
                && !extension_loaded('xdebug')
            );
        }

        $this->setShared('loader', $loader);
        $this->setShared('app', $this);

        $rootDir = $this->getRootDir();
        $appDir = $rootDir . '/app';
        $appNamespace = 'App';
        $publicDir = $_SERVER['DOCUMENT_ROOT'] !== '' ? $_SERVER['DOCUMENT_ROOT'] : $rootDir . '/public';

        if (!str_starts_with($class, 'ManaPHP\\')) {
            $appDir = dirname($this->class_file);
            $appNamespace = substr($class, 0, strrpos($class, '\\'));
            $publicDir = $rootDir . '/public';
        }

        $this->alias->set('@public', $publicDir);
        $this->alias->set('@app', $appDir);
        $this->loader->registerNamespaces([$appNamespace => $appDir]);

        $this->alias->set('@views', $appDir . '/Views');

        $this->alias->set('@root', $rootDir);
        $this->alias->set('@data', $rootDir . '/data');
        $this->alias->set('@tmp', $rootDir . '/tmp');
        $this->alias->set('@resources', $rootDir . '/Resources');
        $this->alias->set('@config', $rootDir . '/config');

        $web = '';
        if ($_SERVER['DOCUMENT_ROOT'] !== '') {
            $web = substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/'));
            if (str_ends_with($web, '/public')) {
                $web = substr($web, 0, -7);
            }
        }
        $this->alias->set('@web', $web);
        $this->alias->set('@asset', $web);

        if ($_SERVER['DOCUMENT_ROOT'] === '') {
            $_SERVER['DOCUMENT_ROOT'] = dirname($_SERVER['SCRIPT_FILENAME']);
        }
    }

    /**
     * @return string
     */
    public function getRootDir()
    {
        if (!str_starts_with(static::class, 'ManaPHP\\')) {
            return dirname($this->class_file, 2);
        } elseif ($_SERVER['DOCUMENT_ROOT'] !== ''
            && $_SERVER['DOCUMENT_ROOT'] === dirname($_SERVER['SCRIPT_FILENAME'])
        ) {
            return dirname($_SERVER['DOCUMENT_ROOT']);
        } else {
            $rootDir = realpath(dirname($_SERVER['SCRIPT_FILENAME']));
            if (is_file($rootDir . '/index.php')) {
                $rootDir = dirname($rootDir);
            }
            return $rootDir;
        }
    }

    /**
     * @return string
     */
    public function getFactory()
    {
        return MANAPHP_CLI ? 'ManaPHP\Cli\Factory' : 'ManaPHP\Mvc\Factory';
    }

    /**
     * @param string $name
     * @param mixed  $definition
     *
     * @return static
     */
    public function setShared($name, $definition)
    {
        $this->container->setShared($name, $definition);

        return $this;
    }

    /**
     * @return void
     */
    public function registerConfigure()
    {
        $configure = $this->configure;

        if ($configure->timezone) {
            date_default_timezone_set($configure->timezone);
        }
        $this->setShared('crypt', ['master_key' => $configure->master_key]);

        $configure->registerAliases();

        $app_dir = scandir($this->alias->resolve('@app'));

        if (in_array('Router.php', $app_dir, true)) {
            $this->setShared('router', 'App\\Router');
        }

        $configure->registerComponents();

        if (MANAPHP_CLI) {
            $configure->registerCommands();
        }

        $configure->registerTracers();

        $configure->registerAspects();
        $configure->registerServices();
        $configure->registerPlugins();
        $configure->registerListeners();
    }

    /**
     * @param \Throwable $exception
     *
     * @return void
     */
    public function handleException($exception)
    {
        $this->errorHandler->handle($exception);
    }

    public function main()
    {
        if (LocalFS::fileExists('@config/.env')) {
            $this->dotenv->load('@config/.env');
        }

        if (LocalFS::fileExists('@config/app.php')) {
            $this->configure->load();
        }

        $this->registerConfigure();

        if (!MANAPHP_CLI) {
            $this->fireEvent('request:begin');
        }
    }

    public function cli()
    {
        $this->cliRunner->run();
    }
}
