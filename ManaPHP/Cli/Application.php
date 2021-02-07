<?php

namespace ManaPHP\Cli;

use ManaPHP\Exception\AbortException;
use ManaPHP\Logging\Logger\LogCategorizable;
use Swoole\Coroutine;
use Swoole\Event;
use Swoole\Runtime;
use Throwable;

/**
 * @property-read \ManaPHP\Cli\HandlerInterface $cliHandler
 */
class Application extends \ManaPHP\Application implements LogCategorizable
{
    /**
     * @var int
     */
    protected $exit_code;

    /**
     * @return string
     */
    public function categorizeLog()
    {
        return 'cli';
    }

    /**
     * @param \ManaPHP\Loader $loader
     */
    public function __construct($loader = null)
    {
        define('MANAPHP_CLI', true);

        parent::__construct($loader);
    }

    public function getFactory()
    {
        return 'ManaPHP\Cli\Factory';
    }

    /**
     * @return void
     */
    public function handle()
    {
        $args = implode(' ', array_slice($GLOBALS['argv'], 1));
        $this->logger->info(['command line: :cmd', 'cmd' => basename($GLOBALS['argv'][0]) . ' ' . $args]);

        try {
            $this->exit_code = $this->cliHandler->handle();
        } catch (AbortException $exception) {
            $this->exit_code = 0;
        } catch (\ManaPHP\Cli\Request\Exception $exception) {
            $this->exit_code = 254;
            $this->errorHandler->handle($exception);
        } catch (Throwable $throwable) {
            $this->exit_code = 255;
            $this->errorHandler->handle($throwable);
        }
    }

    public function main()
    {
        $this->dotenv->load();
        $this->configure->load();

        $this->registerConfigure();

        if (MANAPHP_COROUTINE_ENABLED) {
            Runtime::enableCoroutine(true);
            Coroutine::create([$this, 'handle']);
            Event::wait();
        } else {
            $this->handle();
        }

        exit($this->exit_code);
    }

    public function cli()
    {
        $this->main();
    }
}