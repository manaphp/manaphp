<?php

namespace ManaPHP\Cli;

use ManaPHP\Di\Container;

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
        $providers = $this->getProviders();
        /** @var \ManaPHP\Di\ContainerInterface $container */
        $container = new Container($providers);
        $definitions = $this->container->getDefinitions();
        foreach ($container->getDefinitions() as $name => $definition) {
            if (!isset($definitions[$name]) || $definitions[$name] !== $definition) {
                $this->setShared($name, $definition);
            }
        }

        $this->main();
    }
}