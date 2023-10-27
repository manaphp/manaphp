<?php
declare(strict_types=1);

namespace ManaPHP\Cli;

use JetBrains\PhpStorm\NoReturn;
use ManaPHP\BootstrapperInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception\AbortException;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Swoole\Coroutine;
use Swoole\Event;
use Swoole\Runtime;
use Throwable;

class Server implements ServerInterface
{
    #[Autowired] protected ContainerInterface $container;
    #[Autowired] protected LoggerInterface $logger;
    #[Autowired] protected ErrorHandlerInterface $errorHandler;
    #[Autowired] protected HandlerInterface $handler;

    #[Autowired] protected array $bootstrappers = [];

    protected int $exit_code;

    protected function bootstrap(): void
    {
        foreach ($this->bootstrappers as $name) {
            /** @var BootstrapperInterface $bootstrapper */
            $bootstrapper = $this->container->get($name);
            $bootstrapper->bootstrap();
        }
    }

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

            $this->bootstrap();

            Coroutine::create([$this, 'handle']);
            Event::wait();
        } else {
            $this->bootstrap();

            $this->handle();
        }

        exit($this->exit_code);
    }
}