<?php

namespace ManaPHP\Tracer;

use ManaPHP\Component;
use ManaPHP\Helper\LocalFS;

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
}