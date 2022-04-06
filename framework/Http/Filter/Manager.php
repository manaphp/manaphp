<?php
declare(strict_types=1);

namespace ManaPHP\Http\Filter;

use ManaPHP\Component;

/**
 * @property-read \Psr\Container\ContainerInterface $container
 * @property-read \ManaPHP\ConfigInterface          $config
 */
class Manager extends Component implements ManagerInterface
{
    public function register(): void
    {
        $filters = $this->config->get('filters', []);

        foreach ($filters as $filter) {
            $instance = $this->container->get($filter);
            foreach (class_implements($instance) as $interface) {
                if (str_ends_with($interface, 'FilterInterface')) {
                    foreach (get_class_methods($interface) as $method) {
                        $event = 'request:' . lcfirst(substr($method, 2));
                        $this->attachEvent($event, [$instance, $method]);
                    }
                }
            }
        }

    }
}