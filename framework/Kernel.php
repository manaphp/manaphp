<?php
declare(strict_types=1);

namespace ManaPHP;

use ManaPHP\Di\Container;
use ManaPHP\Di\ContainerInterface;

/**
 * @property-read \ManaPHP\EnvInterface    $env
 * @property-read \ManaPHP\ConfigInterface $config
 * @property-read \ManaPHP\AliasInterface  $alias
 */
class Kernel extends Component
{
    protected string $rootDir;

    public function __construct(string $rootDir)
    {
        $this->rootDir = $rootDir;

        $container = new Container();
        $this->setContainer($container);

        $GLOBALS['ManaPHP\Di\ContainerInterface'] = $container;

        if (!defined('MANAPHP_COROUTINE_ENABLED')) {
            define(
                'MANAPHP_COROUTINE_ENABLED', PHP_SAPI === 'cli'
                && extension_loaded('swoole')
                && !extension_loaded('xdebug')
            );
        }

        $this->alias->set('@public', "$rootDir/public");
        $this->alias->set('@app', "$rootDir/app");
        $this->alias->set('@views', "$rootDir/app/Views");
        $this->alias->set('@root', $rootDir);
        $this->alias->set('@runtime', "$rootDir/runtime");
        $this->alias->set('@resources', "$rootDir/Resources");
        $this->alias->set('@config', "$rootDir/config");
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    public function start(string $server): void
    {
        $this->env->load();
        $this->config->load();

        if (($timezone = $this->config->get('timezone', '')) !== '') {
            date_default_timezone_set($timezone);
        }

        foreach ($this->config->get('aliases', []) as $k => $v) {
            $this->alias->set($k, $v);
        }

        foreach ($this->config->get('factories', []) as $interface => $definitions) {
            foreach ($definitions as $name => $definition) {
                if (is_string($definition) && $definition[0] === '#') {
                    $definition = "@$interface$definition";
                }

                $this->container->set("$interface#$name", $definition);

                if ($name === 'default') {
                    $this->container->set($interface, "#$name");
                }
            }
        }

        foreach ($this->config->get('dependencies') as $id => $definition) {
            $this->container->set($id, $definition);
        }

        foreach ($this->config->get('bootstrappers') as $key => $value) {
            /** @var \ManaPHP\BootstrapperInterface $bootstrapper */
            if (is_int($key)) {
                $bootstrapper = $this->container->get($value);
            } else {
                $this->container->set($key, $value);
                $bootstrapper = $this->container->get($key);
            }

            $bootstrapper->bootstrap($this->container);
        }

        $this->container->get($server)->start();
    }
}