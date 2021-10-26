<?php

namespace ManaPHP\Tracer;

use ManaPHP\Component;
use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Helper\Str;

/**
 * @property-read \ManaPHP\ConfigInterface $config
 */
class Manager extends Component implements ManagerInterface
{
    /**
     * @var array
     */
    protected $tracers = [];

    /**
     * @return array
     */
    public function getTracers()
    {
        if ($this->tracers === []) {
            $tracers = [];

            foreach (LocalFS::glob('@manaphp/Tracers/?*Tracer.php') as $file) {
                $name = basename($file, 'Tracer.php');
                $tracers[lcfirst($name)] = "ManaPHP\Tracers\\{$name}Tracer";
            }

            foreach (LocalFS::glob('@app/Tracers/?*Tracer.php') as $file) {
                $name = basename($file, 'Tracer.php');
                $tracers[lcfirst($name)] = "App\Tracers\\{$name}Tracer";
            }

            ksort($tracers);

            $this->tracers = $tracers;
        }

        return $this->tracers;
    }

    public function listen()
    {
        $tracers = $this->config->get('tracers');

        if (in_array('*', $tracers, true)) {
            foreach ($this->getTracers() as $definition) {
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
                    if (($definition = $this->getTracers()[$camelizedTracer] ?? null) === null) {
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