<?php
declare(strict_types=1);

namespace ManaPHP\Cli;

use JetBrains\PhpStorm\NoReturn;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Exception\AbortException;
use Psr\Log\LoggerInterface;
use Swoole\Coroutine;
use Swoole\Event;
use Swoole\Runtime;
use Throwable;

class Server implements ServerInterface
{
    #[Inject] protected LoggerInterface $logger;
    #[Inject] protected ErrorHandlerInterface $errorHandler;
    #[Inject] protected HandlerInterface $handler;

    protected int $exit_code;

    /**
     * @noinspection PhpUnusedLocalVariableInspection
     * @noinspection PhpRedundantCatchClauseInspection
     */
    public function handle(): void
    {
        $args = implode(' ', array_slice($GLOBALS['argv'], 1));
        $this->logger->info('command line: {0}', [basename($GLOBALS['argv'][0]) . ' ' . $args]);

        try {
            $this->exit_code = $this->handler->handle($GLOBALS['argv']);
        } catch (AbortException $exception) {
            $this->exit_code = 0;
        } catch (OptionsException $exception) {
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