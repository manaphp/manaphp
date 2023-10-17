<?php
declare(strict_types=1);

namespace ManaPHP;

use JetBrains\PhpStorm\NoReturn;
use ManaPHP\Di\ConfigInterface;
use ManaPHP\Di\Container;

class Application
{
    protected string $root;
    protected Container $container;

    public function __construct(string $root)
    {
        $this->root = $root;

        if (!defined('MANAPHP_COROUTINE_ENABLED')) {
            define('MANAPHP_COROUTINE_ENABLED', $this->detectCoroutineCanEnabled());
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
        return PHP_SAPI === 'cli' && extension_loaded('swoole');
    }

    protected function loadDependencies(): void
    {
        $dependencies = [];
        foreach (glob("$this->root/config/*.php") as $item) {
            $file = pathinfo($item, PATHINFO_BASENAME);
            $dependencies += require $item;
        }

        foreach ($dependencies as $id => $definition) {
            $this->container->set($id, $definition);
        }

        $this->container->get(ConfigInterface::class)->set('dependencies', $dependencies);
    }

    #[NoReturn]
    public function start(string $server): void
    {
        $this->container->get(EnvInterface::class)->load();

        $this->loadDependencies();

        $this->container->get(KernelInterface::class)->boot();

        /** @var string|ServerInterface $server */
        $this->container->get($server)->start();
    }
}