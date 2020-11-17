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

        foreach ($this->_di->getDefinitions() as $name => $_) {
            if (str_ends_with($name, 'Plugin')) {
                $plugins[] = basename($name, 'Plugin');
            }
        }

        $this->console->writeLn(json_stringify($plugins));
    }
}