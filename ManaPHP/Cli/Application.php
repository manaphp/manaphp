<?php

namespace ManaPHP\Cli;

use ManaPHP\Logger\LogCategorizable;

/**
 * Class ManaPHP\Cli\Application
 *
 * @package application
 *
 * @property-read \ManaPHP\Cli\HandlerInterface $cliHandler
 */
class Application extends \ManaPHP\Application implements LogCategorizable
{
    /**
     * @return string
     */
    public function categorizeLog()
    {
        return 'cli';
    }

    /**
     * Application constructor.
     *
     * @param \ManaPHP\Loader $loader
     */
    public function __construct($loader = null)
    {
        parent::__construct($loader);

        if ($appDir = $this->alias->get('@app')) {
            if (is_dir("$appDir/Cli")) {
                $this->alias->set('@cli', "$appDir/Cli/Controllers");
                if ($appNamespace = $this->alias->get('@ns.app')) {
                    $this->alias->set('@ns.cli', "$appNamespace/Cli/Controllers");
                }
            } elseif (($calledClass = get_called_class()) !== __CLASS__) {
                $this->alias->set('@cli', "$appDir/Controllers");
                $this->alias->set('@ns.cli', substr($calledClass, 0, strrpos($calledClass, '\\') + 1) . 'Controllers');
            }
        }
    }

    public function getDi()
    {
        if (!$this->_di) {
            $this->_di = new Factory();
        }
        return $this->_di;
    }

    public function registerServices()
    {
        $this->configure->bootstraps = array_diff($this->configure->bootstraps, ['debugger']);

        parent::registerServices();
    }

    public function main()
    {
        $this->dotenv->load();
        $this->configure->load();

        $this->registerServices();

        $this->logger->info(['command line: :cmd', 'cmd' => basename($GLOBALS['argv'][0]) . ' ' . implode(' ', array_slice($GLOBALS['argv'], 1))]);

        try {
            exit($this->cliHandler->handle());
        } /** @noinspection PhpUndefinedClassInspection */
        catch (\Exception $e) {
            $this->errorHandler->handle($e);
        } catch (\Error $e) {
            $this->errorHandler->handle($e);
        }
    }
}