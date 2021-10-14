<?php

namespace ManaPHP\Tracers;

use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Helper\Str;

/**
 * @property-read \ManaPHP\Tracer\ManagerInterface $tracerManager
 */
class Provider extends \ManaPHP\Di\Provider
{
    /**
     * @param \ManaPHP\Di\ContainerInterface $container
     */
    public function boot($container)
    {
        $tracers = $container->get('config')->get('tracers');
        $tracerManager = $container->get('tracerManager');

        if (in_array('*', $tracers, true)) {
            foreach ($tracerManager->getTracers() as $definition) {
                $container->get($definition);
            }
        } else {
            foreach ($tracers as $tracer) {
                if (str_contains($tracer, '\\')) {
                    $container->get($tracer);
                } else {
                    $camelizedTracer = Str::camelize($tracer);
                    if (($definition = $tracerManager->getTracers()[$camelizedTracer] ?? null) === null) {
                        throw new InvalidArgumentException("$camelizedTracer Tracer is not exists");
                    } else {
                        $container->get($definition);
                    }
                }
            }
        }
    }
}