<?php

namespace ManaPHP\Providers;

use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Helper\Str;
use ManaPHP\Provider;

/**
 * @property-read \ManaPHP\ConfigInterface         $config
 * @property-read \ManaPHP\Tracer\ManagerInterface $tracerManager
 */
class TracerProvider extends Provider
{
    public function boot()
    {
        $tracers = $this->config->get('tracers');

        if (in_array('*', $tracers, true)) {
            foreach ($this->tracerManager->getTracers() as $definition) {
                /** @var \ManaPHP\Tracer $tracer */
                $tracer = $this->container->get($definition);
                $tracer->listen();
            }
        } else {
            foreach ($tracers as $tracer) {
                if (str_contains($tracer, '\\')) {
                    $tracer = $this->container->get($tracer);
                } else {
                    $camelizedTracer = Str::camelize($tracer);
                    if (($definition = $this->tracerManager->getTracers()[$camelizedTracer] ?? null) === null) {
                        throw new InvalidArgumentException("$camelizedTracer Tracer is not exists");
                    } else {
                        $tracer = $this->container->get($definition);
                    }
                }
                $tracer->listen();
            }
        }
    }
}