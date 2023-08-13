<?php
declare(strict_types=1);

namespace ManaPHP\Cli;

use JetBrains\PhpStorm\NoReturn;
use ManaPHP\Component;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Exception\AbortException;
use ManaPHP\Logging\LoggerInterface;
use Swoole\Coroutine;
use Swoole\Event;
use Swoole\Runtime;
use Throwable;

class Server extends Component implements ServerInterface
{
    #[Inject]
    protected LoggerInterface $logger;
    #[Inject]
    protected ErrorHandlerInterface $errorHandler;
    #[Inject]
    protected HandlerInterface $cliHandler;

    protected int $exit_code;

    /**
     * @noinspection PhpRedundantCatchClauseInspection
     * @noinspection PhpUnusedLocalVariableInspection
     */
    public function handle(): void
    {
        $args = implode(' ', array_slice($GLOBALS['argv'], 1));
        $this->logger->info(sprintf('command line: %s', basename($GLOBALS['argv'][0])) . ' ' . $args);

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

    #[NoReturn]
    public function start(): void
    {
        if (MANAPHP_COROUTINE_ENABLED) {
            Runtime::enableCoroutine();
            Coroutine::create([$this, 'handle']);
            Event::wait();
        } else {
            $this->handle();
        }

        exit($this->exit_code);
    }
}