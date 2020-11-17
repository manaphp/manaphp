<?php

namespace ManaPHP\Cli\Commands;

use ManaPHP\Cli\Command;

class ListCommand extends Command
{
    /**
     * list all components
     *
     * @param bool $verbose
     * @param bool $all
     */
    public function componentsAction($verbose = false, $all = false)
    {
        $components = [];
        foreach ($this->_di->getDefinitions() as $name => $definition) {
            if (!$all) {
                if (fnmatch('*Command', $name) || fnmatch('*Tracer', $name) || fnmatch('*Plugin', $name)) {
                    continue;
                }
            }

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

        if ($verbose) {
            foreach ($components as $name => $component) {
                $this->console->writeLn('  ' . str_pad($name, 24, ' ') . '=> ' . $component);
            }
        } else {
            $this->console->writeLn(json_stringify(array_keys($components)));
        }
    }

    /**
     * list all plugins
     *
     * @param bool $verbose
     */
    public function pluginsAction($verbose = false)
    {
        $tracers = [];
        foreach ($this->_di->getDefinitions('*Plugin') as $name => $definition) {
            $tracers[basename($name, 'Plugin')] = $definition;
        }

        ksort($tracers);

        if ($verbose) {
            foreach ($tracers as $name => $definition) {
                $this->console->writeLn(str_pad($name, 16, ' ') . ' => ' . $definition);
            }
        } else {
            $this->console->writeLn(json_stringify(array_keys($tracers)));
        }
    }

    /**
     * list all tracers
     *
     * @param bool $verbose
     */
    public function tracersAction($verbose = false)
    {
        $tracers = [];
        foreach ($this->_di->getDefinitions('*Tracer') as $name => $definition) {
            $tracers[basename($name, 'Tracer')] = $definition;
        }

        ksort($tracers);

        if ($verbose) {
            foreach ($tracers as $name => $definition) {
                $this->console->writeLn(str_pad($name, 16, ' ') . ' => ' . $definition);
            }
        } else {
            $this->console->writeLn(json_stringify(array_keys($tracers)));
        }
    }
}