<?php
declare(strict_types=1);

namespace ManaPHP;

use JetBrains\PhpStorm\NoReturn;
use ManaPHP\Di\ConfigInterface;
use ManaPHP\Di\Container;
use ManaPHP\Eventing\ListenersInterface;
use ManaPHP\Eventing\TracerInterface;

class Kernel
{
    protected string $root;
    protected Container $container;

    protected array $bootstrappers
        = [
            ListenersInterface::class,
            TracerInterface::class,
        ];

    public function __construct(string $root)
    {
        $this->root = $root;

        if (!\defined('MANAPHP_COROUTINE_ENABLED')) {
            \define('MANAPHP_COROUTINE_ENABLED', $this->detectCoroutineCanEnabled());
        }

        $this->container = new Container([
            'Psr\SimpleCache\CacheInterface'                => 'ManaPHP\Caching\SimpleCache',
            'Psr\EventDispatcher\EventDispatcherInterface'  => 'ManaPHP\Eventing\EventDispatcherInterface',
            'Psr\EventDispatcher\ListenerProviderInterface' => 'ManaPHP\Eventing\ListenerProviderInterface',
            'ManaPHP\AliasInterface'                        => [
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
    }

    public function detectCoroutineCanEnabled(): bool
    {
        return PHP_SAPI === 'cli' && \extension_loaded('swoole');
    }

    protected function loadConfig(): ConfigInterface
    {
        $configs = [];
        foreach (glob("$this->root/config/*.php") as $item) {
            $configs += require $item;
        }

        foreach ($configs as $id => $definition) {
            $this->container->set($id, $definition);
        }

        $config = $this->container->get(ConfigInterface::class);
        foreach ($configs as $id => $definition) {
            $config->set($id, $definition);
        }

        return $config;
    }

    protected function bootstrap(ConfigInterface $config): void
    {
        $bootstrappers = $config->get(static::class)['bootstrappers'] ?? $this->bootstrappers;
        foreach ($bootstrappers as $name) {
            /** @var BootstrapperInterface $bootstrapper */
            $bootstrapper = $this->container->get($name);
            $bootstrapper->bootstrap();
        }
    }

    #[NoReturn]
    public function start(string $server): void
    {
        $this->container->get(EnvInterface::class)->load();

        $config = $this->loadConfig();

        if (($timezone = $config->get('timezone')) !== null) {
            date_default_timezone_set($timezone);
        }

        foreach ($config->get('aliases', []) as $aliases) {
            foreach ($aliases as $k => $v) {
                $this->container->get(AliasInterface::class)->set($k, $v);
            }
        }

        $this->bootstrap($config);

        /** @var string|ServerInterface $server */
        $this->container->get($server)->start();
    }
}