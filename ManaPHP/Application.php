<?php

namespace ManaPHP;

use ManaPHP\Application\Provider;
use ManaPHP\Di\Container;
use ManaPHP\Di\Injector;
use ManaPHP\Helper\LocalFS;
use ReflectionClass;
use ManaPHP\Service\Provider as ServiceProvider;
use ManaPHP\Tracing\Provider as TracingProvider;
use ManaPHP\Plugin\Provider as PluginProvider;

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
     * @var \ManaPHP\Di\ContainerInterface
     */
    protected $container;

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

        $this->container = new Container(['alias' => Alias::class]);
        $GLOBALS['CONTAINER'] = $this->container;
        $this->setInjector(new Injector($this->container));

        if (!defined('MANAPHP_COROUTINE_ENABLED')) {
            define(
                'MANAPHP_COROUTINE_ENABLED', PHP_SAPI === 'cli'
                && extension_loaded('swoole')
                && !extension_loaded('xdebug')
            );
        }

        $this->container->set('loader', $loader);
        $this->container->set('app', $this);

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

        $this->container->addProviders($this->getProviders());
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
     * @return array
     */
    public function getProviders()
    {
        return [
            Provider::class,
            ServiceProvider::class,
            TracingProvider::class,
            PluginProvider::class,
        ];
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
        $this->container->set('crypt', ['master_key' => $configure->master_key]);

        $configure->registerAliases();

        $app_dir = scandir($this->alias->resolve('@app'));

        if (in_array('Router.php', $app_dir, true)) {
            $this->container->set('router', 'App\\Router');
        }

        $configure->registerComponents();

        $configure->registerAspects();
        $configure->registerListeners();

        foreach ($this->container->getProviders() as $provider) {
            /** @var \ManaPHP\Di\ProviderInterface $instance */
            $instance = $this->container->get($provider);
            $instance->boot($this->container);
        }
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
