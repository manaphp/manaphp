<?php

namespace ManaPHP\Cli;

/**
 * Class ManaPHP\Cli\Application
 *
 * @package application
 *
 * @property \ManaPHP\Cli\HandlerInterface $cliHandler
 */
class Application extends \ManaPHP\Application
{
    /**
     * Application constructor.
     *
     * @param \ManaPHP\Loader      $loader
     * @param \ManaPHP\DiInterface $dependencyInjector
     *
     * @throws \ManaPHP\Application\Exception
     */
    public function __construct($loader, $dependencyInjector = null)
    {
        parent::__construct($loader, $dependencyInjector);

        foreach (['@app/Cli/Controllers', '@app/Controllers', '@app'] as $dir) {
            if ($this->filesystem->dirExists($dir)) {
                $this->alias->set('@cli', $this->alias->resolve($dir));
                $this->alias->set('@ns.cli', $this->alias->resolveNS(strtr($dir, ['@app' => '@ns.app', '/' => '\\'])));
                break;
            }
        }
    }

    public function registerServices()
    {
        parent::registerServices();

        $this->_dependencyInjector->setShared('cliHandler', 'ManaPHP\Cli\Handler');
        $this->_dependencyInjector->setShared('console', 'ManaPHP\Cli\Console');
        $this->_dependencyInjector->setShared('arguments', 'ManaPHP\Cli\Arguments');
        $this->_dependencyInjector->setShared('cliRouter', 'ManaPHP\Cli\Router');
    }

    public function main()
    {
        $this->registerServices();

        exit($this->cliHandler->handle());
    }
}