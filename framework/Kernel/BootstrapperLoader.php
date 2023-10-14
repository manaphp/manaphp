<?php
declare(strict_types=1);

namespace ManaPHP\Kernel;

use ManaPHP\BootstrapperInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\ContainerInterface;

class BootstrapperLoader implements BootstrapperLoaderInterface
{
    #[Autowired] protected ContainerInterface $container;
    #[Autowired] protected array $bootstrappers = [];

    public function load(): void
    {
        foreach ($this->bootstrappers as $key => $value) {
            /** @var BootstrapperInterface $bootstrapper */
            if (is_int($key)) {
                $bootstrapper = $this->container->get($value);
            } else {
                $this->container->set($key, $value);
                $bootstrapper = $this->container->get($key);
            }

            $bootstrapper->bootstrap($this->container);
        }
    }
}