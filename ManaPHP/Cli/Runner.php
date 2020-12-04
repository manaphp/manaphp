<?php

namespace ManaPHP\Cli;

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
        $factory = $this->getFactory();
        /** @var \ManaPHP\DiInterface $di */
        $di = new $factory();
        $definitions = $this->_di->getDefinitions();
        foreach ($di->getDefinitions() as $name => $definition) {
            if (!isset($definitions[$name]) || $definitions[$name] !== $definition) {
                $this->setShared($name, $definition);
            }
        }

        $this->main();
    }
}