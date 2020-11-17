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
        foreach ($this->_di->getDefinitions('*Tracer') as $name => $_) {
            $tracers[] = basename($name, 'Tracer');
        }

        $this->console->writeLn(json_stringify($tracers));
    }
}