<?php

namespace ManaPHP\Cli\Commands;

use ManaPHP\Cli\Command;
use ManaPHP\Component;
use ReflectionClass;
use ReflectionMethod;

class ListCommand extends Command
{
    /**
     * list component options
     */
    public function optionsAction()
    {
        $components = [];

        foreach ((new ReflectionClass(Component::class))->getProperties() as $property) {
            $component_options[$property->getName()] = 1;
        }

        foreach ($this->container->getDefinitions() as $name => $definition) {
            if (fnmatch('*Command', $name) || fnmatch('*Tracer', $name) || fnmatch('*Plugin', $name)) {
                continue;
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
            } elseif (is_object($definition)) {
                $className = get_class($definition);
            } else {
                $className = '?';
            }

            $options = [];

            if (class_exists($className)) {
                if (method_exists($className, '__construct')) {
                    $parameters = (new ReflectionMethod($className, '__construct'))->getParameters();
                    if (count($parameters) === 1) {
                        $parameter = $parameters[0];
                        if ($parameter->getName() !== 'options') {
                            $options = [$parameter->getName()];
                        }
                    }
                }

                if ($options === []) {
                    $rc = new ReflectionClass($className);
                    foreach ($rc->getProperties() as $rp) {
                        $property = $rp->getName();

                        if ($property[0] !== '_' || isset($component_options[$property])) {
                            continue;
                        }

                        $options[] = substr($property, 1);
                    }
                }
            } else {
                $options[] = '?';
            }

            $components[$name] = $options;
        }

        ksort($components);

        foreach ($components as $name => $component) {
            $this->console->writeLn('  ' . str_pad($name, 24) . '=> ' . json_stringify($component));
        }
    }

    /**
     * list all components
     *
     * @param bool $verbose
     * @param bool $all
     *
     * @return void
     */
    public function componentsAction($verbose = false, $all = false)
    {
        $components = [];
        foreach ($this->container->getDefinitions() as $name => $definition) {
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
            } elseif (is_object($definition)) {
                $className = get_class($definition);
            } else {
                $className = '?';
            }

            $components[$name] = $className;
        }

        ksort($components);

        if ($verbose) {
            foreach ($components as $name => $component) {
                $this->console->writeLn('  ' . str_pad($name, 24) . '=> ' . $component);
            }
        } else {
            $this->console->writeLn(json_stringify(array_keys($components)));
        }
    }

    /**
     * list all plugins
     *
     * @param bool $verbose
     *
     * @return void
     */
    public function pluginsAction($verbose = false)
    {
        $tracers = [];
        foreach ($this->container->getDefinitions('*Plugin') as $name => $definition) {
            $tracers[basename($name, 'Plugin')] = $definition;
        }

        ksort($tracers);

        if ($verbose) {
            foreach ($tracers as $name => $definition) {
                $this->console->writeLn(str_pad($name, 16) . ' => ' . $definition);
            }
        } else {
            $this->console->writeLn(json_stringify(array_keys($tracers)));
        }
    }

    /**
     * list all tracers
     *
     * @param bool $verbose
     *
     * @return void
     */
    public function tracersAction($verbose = false)
    {
        $tracers = [];
        foreach ($this->container->getDefinitions('*Tracer') as $name => $definition) {
            $tracers[basename($name, 'Tracer')] = $definition;
        }

        ksort($tracers);

        if ($verbose) {
            foreach ($tracers as $name => $definition) {
                $this->console->writeLn(str_pad($name, 16) . ' => ' . $definition);
            }
        } else {
            $this->console->writeLn(json_stringify(array_keys($tracers)));
        }
    }
}