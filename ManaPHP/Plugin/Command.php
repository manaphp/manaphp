<?php

namespace ManaPHP\Plugin;

class Command extends \ManaPHP\Cli\Command
{
    /**
     * list all plugins
     */
    public function defaultAction()
    {
        $plugins = [];

        foreach ($this->_di->getDefinitions('*Plugin') as $name => $_) {
            $plugins[] = basename($name, 'Plugin');
        }

        $this->console->writeLn(json_stringify($plugins));
    }
}