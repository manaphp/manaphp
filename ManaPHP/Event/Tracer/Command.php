<?php

namespace ManaPHP\Event\Tracer;

class Command extends \ManaPHP\Cli\Command
{
    /**
     * list all tracers
     */
    public function defaultAction()
    {
        $tracers = [];
        foreach ($this->_di->getDefinitions() as $name => $_) {
            if (str_ends_with($name, 'Tracer')) {
                $tracers[] = basename($name, 'Tracer');
            }
        }

        $this->console->writeLn(json_stringify($tracers));
    }
}