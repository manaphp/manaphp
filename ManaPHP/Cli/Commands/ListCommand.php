<?php

namespace ManaPHP\Cli\Commands;

use ManaPHP\Cli\Command;

class ListCommand extends Command
{
    /**
     * list all components
     */
    public function componentsAction()
    {
        $components = [];
        foreach ($this->_di->getDefinitions() as $name => $definition) {
            if (is_string($definition)) {
                $className = $definition;
            } elseif (is_array($definition)) {
                if (isset($definition['class']) && is_string($definition['class'])) {
                    $className = $definition['class'];
                } elseif (isset($definition[0]) && is_string($definition[0])) {
                    $className = $definition[0];
                } else {
                    $className = '?';
                }
            } else {
                $className = '?';
            }

            $components[$name] = $className;
        }

        ksort($components);

        foreach ($components as $name => $component) {
            $this->console->writeLn('  ' . str_pad($name, 24, ' ') . '=> ' . $component);
        }
    }

    /**
     * list all plugins
     */
    public function pluginsAction()
    {
        $plugins = [];

        foreach ($this->_di->getDefinitions('*Plugin') as $name => $_) {
            $plugins[] = basename($name, 'Plugin');
        }

        $this->console->writeLn(json_stringify($plugins));
    }

    /**
     * list all tracers
     */
    public function tracersAction()
    {
        $tracers = [];
        foreach ($this->_di->getDefinitions('*Tracer') as $name => $_) {
            $tracers[] = basename($name, 'Tracer');
        }

        $this->console->writeLn(json_stringify($tracers));
    }
}