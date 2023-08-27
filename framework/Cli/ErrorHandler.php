<?php
declare(strict_types=1);

namespace ManaPHP\Cli;

use ManaPHP\Di\Attribute\Inject;
use Psr\Log\LoggerInterface;
use Throwable;

class ErrorHandler implements ErrorHandlerInterface
{
    #[Inject] protected LoggerInterface $logger;

    public function handle(Throwable $throwable): void
    {
        $this->logger->error('', ['exception' => $throwable]);
        echo($throwable);
    }
}