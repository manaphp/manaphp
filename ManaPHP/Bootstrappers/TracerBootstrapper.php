<?php

namespace ManaPHP\Bootstrappers;

use ManaPHP\BootstrapperInterface;
use ManaPHP\Component;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Helper\Str;

/**
 * @property-read \ManaPHP\ConfigInterface $config
 */
class TracerBootstrapper extends Component implements BootstrapperInterface
{
    /**
     * @var array
     */
    protected $tracers = ['*'];

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['tracers'])) {
            $this->tracers = $options['tracers'];
        }
    }

    public function bootstrap()
    {
        /** @var \ManaPHP\Tracer $tracer */

        if (in_array('*', $this->tracers, true)) {
            foreach (LocalFS::glob('@manaphp/Tracers/?*Tracer.php') as $file) {
                $name = basename($file, 'Tracer.php');
                $tracer = $this->container->get("ManaPHP\Tracers\\{$name}Tracer");
                $tracer->listen();
            }
        } else {
            foreach ($this->tracers as $name) {
                $name = Str::camelize($name);
                $tracer = $this->container->get("ManaPHP\Tracers\\{$name}Tracer");
                $tracer->listen();
            }
        }

        foreach (LocalFS::glob('@app/Tracers/?*Tracer.php') as $file) {
            $tracer = $this->container->get('App\Tracers\\' . basename($file, '.php'));
            $tracer->listen();
        }

        foreach (LocalFS::glob('@app/Areas/*', GLOB_ONLYDIR) as $item) {
            $area = basename($item);
            foreach (LocalFS::glob("$item/Tracers/?*Tracer.php") as $file) {
                $tracer = $this->container->get("App\\Areas\\$area\\Tracers\\" . basename($file, '.php'));
                $tracer->listen();
            }
        }
    }
}