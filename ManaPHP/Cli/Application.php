<?php

namespace ManaPHP\Cli;

use ManaPHP\Exception\AbortException;
use ManaPHP\Logger\LogCategorizable;
use Swoole\Coroutine;
use Swoole\Event;
use Throwable;

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
     * @var int
     */
    protected $_exit_code = 255;

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
                $this->alias->set('@ns.cli', "App\\Cli\\Controllers");
            } elseif (($class = static::class) !== __CLASS__) {
                $this->alias->set('@cli', "$appDir/Controllers");
                $this->alias->set('@ns.cli', substr($class, 0, strrpos($class, '\\') + 1) . 'Controllers');
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

    public function main()
    {
        $this->dotenv->load();
        $this->configure->load();

        $this->registerServices();

        $this->logger->info(['command line: :cmd', 'cmd' => basename($GLOBALS['argv'][0]) . ' ' . implode(' ', array_slice($GLOBALS['argv'], 1))]);

        if (MANAPHP_COROUTINE_ENABLED) {
            Coroutine::create(function () {
                try {
                    $this->_exit_code = $this->cliHandler->handle();
                } catch (AbortException $exception) {
                    $this->_exit_code = 0;
                } catch (Throwable $throwable) {
                    $this->_exit_code = 127;
                    $this->errorHandler->handle($throwable);
                }
            });
            Event::wait();
        } else {
            try {
                $this->_exit_code = $this->cliHandler->handle();
            } catch (AbortException $exception) {
                $this->_exit_code = 0;
            } catch (Throwable $e) {
                $this->_exit_code = 127;
                $this->errorHandler->handle($e);
            }
        }

        exit($this->_exit_code);
    }
}