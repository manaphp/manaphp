<?php
declare(strict_types=1);

namespace ManaPHP;

use JetBrains\PhpStorm\NoReturn;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\ConfigInterface;
use ManaPHP\Di\Proxy;
use ManaPHP\Kernel\BootstrapperLoaderInterface;
use ManaPHP\Kernel\ConfigLoaderInterface;
use Psr\Container\ContainerInterface;

class Kernel
{
    #[Autowired] protected ContainerInterface $container;
    #[Autowired] protected AliasInterface $alias;
    #[Autowired] protected EnvInterface $env;
    #[Autowired] protected ConfigInterface $config;
    #[Autowired] protected ConfigLoaderInterface $configLoader;
    #[Autowired] protected BootstrapperLoaderInterface|Proxy $bootstrapperLoader;

    #[Autowired] protected string $rootDir;

    public function registerDefaultDependencies(): void
    {
        $this->container->set('Psr\SimpleCache\CacheInterface', 'ManaPHP\Caching\SimpleCache');
        $this->container->set(
            'Psr\EventDispatcher\EventDispatcherInterface', 'ManaPHP\Eventing\EventDispatcherInterface'
        );
        $this->container->set(
            'Psr\EventDispatcher\ListenerProviderInterface', 'ManaPHP\Eventing\ListenerProviderInterface'
        );
    }

    public function registerDefaultAliases(): void
    {
        $root = $this->rootDir;

        $this->alias->set('@public', "$root/public");
        $this->alias->set('@app', "$root/app");
        $this->alias->set('@views', "$root/app/Views");
        $this->alias->set('@root', $root);
        $this->alias->set('@runtime', "$root/runtime");
        $this->alias->set('@resources', "$root/resources");
        $this->alias->set('@config', "$root/config");
    }

    public function detectCoroutineCanEnabled(): bool
    {
        return PHP_SAPI === 'cli' && extension_loaded('swoole');
    }

    #[NoReturn]
    public function start(string $server): void
    {
        if (!defined('MANAPHP_COROUTINE_ENABLED')) {
            define('MANAPHP_COROUTINE_ENABLED', $this->detectCoroutineCanEnabled());
        }

        $GLOBALS['Psr\Container\ContainerInterface'] = $this->container;

        $this->registerDefaultDependencies();
        $this->registerDefaultAliases();

        $this->env->load();

        $this->configLoader->load();

        if (($timezone = $this->config->get('timezone')) !== null) {
            date_default_timezone_set($timezone);
        }

        foreach ($this->config->get('aliases', []) as $aliases) {
            foreach ($aliases as $k => $v) {
                $this->alias->set($k, $v);
            }
        }

        foreach ($this->config->get('dependencies', []) as $id => $definition) {
            $this->container->set($id, $definition);
        }

        $this->bootstrapperLoader->load();

        /** @var string|ServerInterface $server */
        $server = $this->container->get($server);
        $server->start();
    }
}