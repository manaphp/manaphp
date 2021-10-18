<?php

namespace ManaPHP\Cli;

use ManaPHP\Di\Container;

/**
 * @property-read \ManaPHP\Di\ContainerInterface $container
 */
class Runner extends Application implements RunnerInterface
{
    /**
     * @param \ManaPHP\Loader $loader
     *
     * @noinspection MagicMethodsValidityInspection
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct($loader = null)
    {

    }

    /**
     * @return void
     */
    public function run()
    {
        /** @var \ManaPHP\Di\ContainerInterface $container */
        $container = new Container();
        $definitions = $this->container->getDefinitions();
        foreach ($container->getDefinitions() as $name => $definition) {
            if (!isset($definitions[$name]) || $definitions[$name] !== $definition) {
                $this->container->set($name, $definition);
            }
        }

        $this->main();
    }
}