<?php
declare(strict_types=1);

namespace ManaPHP\Cli;

use JetBrains\PhpStorm\NoReturn;
use ManaPHP\Component;
use ManaPHP\Exception\AbortException;
use Swoole\Coroutine;
use Swoole\Event;
use Swoole\Runtime;
use Throwable;

/**
 * @property-read \ManaPHP\Logging\LoggerInterface   $logger
 * @property-read \ManaPHP\Cli\ErrorHandlerInterface $errorHandler
 * @property-read \ManaPHP\Cli\HandlerInterface      $cliHandler
 */
class Server extends Component implements ServerInterface
{
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