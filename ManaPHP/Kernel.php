<?php

namespace ManaPHP;

use ManaPHP\Di\Container;

/**
 * @property-read \ManaPHP\EnvInterface    $env
 * @property-read \ManaPHP\ConfigInterface $config
 * @property-read \ManaPHP\AliasInterface  $alias
 */
class Kernel extends Component
{
    /**
     * @var string
     */
    protected $rootDir;

    /**
     * @param string $rootDir
     */
    public function __construct($rootDir)
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
        $this->alias->set('@data', "$rootDir/data");
        $this->alias->set('@tmp', "$rootDir/tmp");
        $this->alias->set('@resources', "$rootDir/Resources");
        $this->alias->set('@config', "$rootDir/config");
    }

    /**
     * @return \ManaPHP\Di\ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @param string $server
     */
    public function start($server)
    {
        $this->env->load();
        $this->config->load();

        if (($timezone = $this->config->get('timezone', '')) !== '') {
            date_default_timezone_set($timezone);
        }

        foreach ($this->config->get('aliases', []) as $k => $v) {
            $this->alias->set($k, $v);
        }

        foreach ($this->config->get('dependencies') as $id => $definition) {
            $this->container->set($id, $definition);
        }

        foreach ($this->config->get('bootstrappers') as $item) {
            /** @var \ManaPHP\BootstrapperInterface $bootstrapper */
            $bootstrapper = $this->container->get($item);
            $bootstrapper->bootstrap();
        }

        $this->container->get($server)->start();
    }
}