<?php

namespace ManaPHP\Cli;

use ManaPHP\Di\FactoryDefault;

/**
 * Class ManaPHP\Cli\Application
 *
 * @package application
 *
 * @property \ManaPHP\Cli\HandlerInterface $cliHandler
 */
class Application extends \ManaPHP\Application
{
    /** @noinspection MagicMethodsValidityInspection */
    /** @noinspection PhpMissingParentConstructorInspection */
    /**
     * Application constructor.
     *
     * @param \ManaPHP\Loader      $loader
     * @param \ManaPHP\DiInterface $dependencyInjector
     */
    public function __construct($loader, $dependencyInjector = null)
    {
        if (get_called_class() === __CLASS__) {
            $this->_dependencyInjector = $dependencyInjector ?: new FactoryDefault();

            $this->_dependencyInjector->setShared('loader', $loader);
            $this->_dependencyInjector->setShared('application', $this);

            $appDir = dirname(get_included_files()[0]) . '/app';
            $appFile = $appDir . '/Application.php';
            if (is_file($appFile)) {
                $str = file_get_contents($appFile);
                if (preg_match('#namespace\s+([\w\\\\]+);#', $str, $match)) {
                    $this->loader->registerNamespaces([$match[1] => $appDir]);

                    $this->alias->set('@root', dirname($appDir));
                    $this->alias->set('@data', '@root/data');
                    $this->alias->set('@app', $appDir);
                    $this->alias->set('@ns.app', $match[1]);
                }
            }
        } else {
            parent::__construct($loader, $dependencyInjector);
        }

        if ($this->alias->has('@app')) {
            foreach (['@app/Cli/Controllers'] as $dir) {
                if ($this->filesystem->dirExists($dir)) {
                    $this->alias->set('@cli', $this->alias->resolve($dir));
                    $this->alias->set('@ns.cli', $this->alias->resolveNS(strtr($dir, ['@app' => '@ns.app', '/' => '\\'])));
                    break;
                }
            }
        }
    }

    public function registerServices()
    {
        $this->configure->bootstraps = array_diff($this->configure->bootstraps, ['debugger']);

        parent::registerServices();

        $this->_dependencyInjector->setShared('cliHandler', 'ManaPHP\Cli\Handler');
        $this->_dependencyInjector->setShared('console', 'ManaPHP\Cli\Console');
        $this->_dependencyInjector->setShared('arguments', 'ManaPHP\Cli\Arguments');
        $this->_dependencyInjector->setShared('cliRouter', 'ManaPHP\Cli\Router');
    }

    public function main()
    {
        if ($this->configFile) {
            $this->configure->loadFile($this->configFile, $this->env);
        }

        $this->registerServices();

        exit($this->cliHandler->handle());
    }
}