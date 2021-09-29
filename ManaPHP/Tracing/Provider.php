<?php

namespace ManaPHP\Tracing;

use ManaPHP\Helper\LocalFS;

class Provider extends \ManaPHP\Di\Provider
{
    public function __construct()
    {
        foreach (LocalFS::glob('@app/Tracers/?*Tracer.php') as $file) {
            $command = basename($file, '.php');
            $this->definitions[lcfirst($command)] = "App\Tracers\\$command";
        }
    }

    /**
     * @param \ManaPHP\Di\ContainerInterface $container
     */
    public function boot($container)
    {
        $tracers = $container->get('configure')->tracers;

        if (in_array('*', $tracers, true)) {
            foreach ($container->getDefinitions('*Tracer') as $name => $_) {
                $container->get($name);
            }
        } else {
            foreach ($tracers as $tracer) {
                $container->get(lcfirst($tracer) . 'Tracer');
            }
        }
    }
}