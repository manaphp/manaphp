<?php
declare(strict_types=1);

namespace ManaPHP\Cli;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Logging\LoggerInterface;
use Throwable;

class ErrorHandler implements ErrorHandlerInterface
{
    #[Inject] protected LoggerInterface $logger;

    public function handle(Throwable $throwable): void
    {
        $this->logger->error($throwable);
        echo($throwable);
    }
}