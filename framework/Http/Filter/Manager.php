<?php
declare(strict_types=1);

namespace ManaPHP\Http\Filter;

use ManaPHP\Component;
use ManaPHP\ConfigInterface;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Event\EventTrait;

class Manager extends Component implements ManagerInterface
{
    use EventTrait;

    #[Inject]
    protected FactoryInterface $factory;
    #[Inject]
    protected ConfigInterface $config;

    public function register(): void
    {
        $filters = $this->config->get('filters', []);

        foreach ($filters as $filter) {
            $instance = $this->factory->get($filter);
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