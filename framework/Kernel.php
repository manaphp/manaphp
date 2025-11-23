<?php

declare(strict_types=1);

namespace ManaPHP;

use JetBrains\PhpStorm\NoReturn;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\ConfigInterface;
use ManaPHP\Di\Container;
use ManaPHP\Eventing\ListenersInterface;
use ManaPHP\Eventing\TracerInterface;
use ManaPHP\Kernel\BootstrapperFactory;
use ReflectionClass;
use function date_default_timezone_set;
use function define;
use function defined;
use function dirname;
use function extension_loaded;
use function get_included_files;
use function glob;

class Kernel
{
    #[Autowired] protected EnvInterface $env;
    #[Autowired] protected ConfigInterface $config;
    #[Autowired] protected AliasInterface $alias;
    #[Autowired] protected BootstrapperFactory $bootstrapperFactory;

    protected string $root;
    protected Container $container;

    protected array $bootstrappers
        = [
            ListenersInterface::class,
            TracerInterface::class,
        ];

    public function __construct(?string $root = null)
    {
        if ($root === null) {
            $root = dirname(get_included_files()[1], 2);
        }

        $this->root = $root;

        if (!defined('MANAPHP_COROUTINE_ENABLED')) {
            define('MANAPHP_COROUTINE_ENABLED', $this->detectCoroutineCanEnabled());
        }

        $this->container = new Container([
            'Psr\SimpleCache\CacheInterface'                => 'ManaPHP\Caching\SimpleCache',
            'Psr\EventDispatcher\EventDispatcherInterface'  => 'ManaPHP\Eventing\EventDispatcherInterface',
            'Psr\EventDispatcher\ListenerProviderInterface' => 'ManaPHP\Eventing\ListenerProviderInterface',
            'ManaPHP\AliasInterface' => [
                'aliases' => [
                    '@manaphp' => __DIR__,
                    '@public'  => "$root/public",
                    '@app'     => "$root/app",
                    '@views'   => "$root/app/Views",
                    '@root'    => $root,
                    '@runtime' => "$root/runtime",
                    '@config'  => "$root/config",
                ]],
        ]);

        $GLOBALS['Psr\Container\ContainerInterface'] = $this->container;

        $this->container->injectProperties($this, new ReflectionClass($this), []);
    }

    public function detectCoroutineCanEnabled(): bool
    {
        return PHP_SAPI === 'cli' && extension_loaded('swoole');
    }

    protected function loadConfig(string $server): void
    {
        $configs = [];
        foreach (glob("$this->root/config/*.php") as $item) {
            $configs += require $item;
        }

        foreach ($configs[ConfigInterface::class]['config'] ?? [] as $key => $val) {
            $this->config->set($key, $val);
        }
        unset($configs[ConfigInterface::class]);

        foreach ($configs as $id => $definition) {
            $this->container->set($id, $definition);
        }

        $this->config->set('server', $server);
        foreach ($configs as $id => $definition) {
            $this->config->set($id, $definition);
        }

        foreach ($configs[ConfigInterface::class]['config'] ?? [] as $key => $val) {
            $this->config->set($key, $val);
        }
    }

    protected function bootstrap(): void
    {
        $bootstrappers = $this->config->get(static::class)['bootstrappers'] ?? $this->bootstrappers;
        foreach ($bootstrappers as $name) {
            if ($name !== '' && $name !== null) {
                $this->bootstrapperFactory->bootstrap($name);
            }
        }
    }

    #[NoReturn]
    public function start(string $server): void
    {
        $this->env->load();

        $this->loadConfig($server);

        if (($timezone = $this->config->get('timezone')) !== null) {
            date_default_timezone_set($timezone);
        }

        foreach ($this->config->get('aliases', []) as $k => $v) {
            $this->alias->set($k, $v);
        }

        $this->bootstrap();

        /** @var string|ServerInterface $server */
        $this->container->get($server)->start();
    }
}
