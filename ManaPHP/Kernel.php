<?php

namespace ManaPHP;

use ManaPHP\Di\Container;
use ManaPHP\Security\Crypt;
use ManaPHP\Security\CryptInterface;

/**
 * @property-read \ManaPHP\DotenvInterface $dotenv
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

        $this->setContainer(new Container());

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

        $this->boot();
    }

    protected function boot()
    {
        $this->dotenv->load('@config/.env');
        $this->config->load();

        if (($timezone = $this->config->get('timezone', '')) !== '') {
            date_default_timezone_set($timezone);
        }
        $this->container->set(
            CryptInterface::class, ['class' => Crypt::class, 'master_key' => $this->config->get('master_key')]
        );

        foreach ($this->config->get('aliases', []) as $k => $v) {
            $this->alias->set($k, $v);
        }

        foreach ($this->config->get('dependencies') as $name => $definition) {
            $this->container->set($name, $definition);
        }

        foreach ($this->config->get('providers', []) as $definition) {
            /** @var \ManaPHP\ProviderInterface $provider */
            $provider = $this->container->get($definition);
            $provider->boot();
        }
    }

    /**
     * @param string $server
     */
    public function start($server)
    {
        $this->container->get($server)->start();
    }
}