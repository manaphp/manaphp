<?php

namespace ManaPHP\Configurators;


use ManaPHP\Component;
use ManaPHP\ConfiguratorInterface;
use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Helper\Str;

/**
 * @property-read \ManaPHP\ConfigInterface         $config
 * @property-read \ManaPHP\Tracer\ManagerInterface $tracerManager
 */
class TracerConfigurator extends Component implements ConfiguratorInterface
{
    public function configure()
    {
        $tracers = $this->config->get('tracers');

        if (in_array('*', $tracers, true)) {
            foreach ($this->tracerManager->getTracers() as $definition) {
                $this->container->get($definition);
            }
        } else {
            foreach ($tracers as $tracer) {
                if (str_contains($tracer, '\\')) {
                    $this->container->get($tracer);
                } else {
                    $camelizedTracer = Str::camelize($tracer);
                    if (($definition = $this->tracerManager->getTracers()[$camelizedTracer] ?? null) === null) {
                        throw new InvalidArgumentException("$camelizedTracer Tracer is not exists");
                    } else {
                        $this->container->get($definition);
                    }
                }
            }
        }
    }
}