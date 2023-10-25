<?php
declare(strict_types=1);

namespace ManaPHP;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\Attribute\Config;
use ManaPHP\Di\ConfigInterface;
use Psr\Container\ContainerInterface;

class Kernel implements KernelInterface
{
    #[Autowired] protected ContainerInterface $container;
    #[Autowired] protected AliasInterface $alias;
    #[Autowired] protected ConfigInterface $config;

    #[Config] protected array $bootstrappers = [];

    public function boot(): void
    {
        if (($timezone = $this->config->get('timezone')) !== null) {
            date_default_timezone_set($timezone);
        }

        foreach ($this->config->get('aliases', []) as $aliases) {
            foreach ($aliases as $k => $v) {
                $this->alias->set($k, $v);
            }
        }

        foreach ($this->bootstrappers as $id) {
            /** @var BootstrapperInterface $bootstrapper */
            $bootstrapper = $this->container->get($id);
            $bootstrapper->bootstrap();
        }
    }
}