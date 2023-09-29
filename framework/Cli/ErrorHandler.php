<?php
declare(strict_types=1);

namespace ManaPHP\Cli;

use ManaPHP\Di\Attribute\Autowired;
use Psr\Log\LoggerInterface;
use Throwable;

class ErrorHandler implements ErrorHandlerInterface
{
    #[Autowired] protected LoggerInterface $logger;

    public function handle(Throwable $throwable): void
    {
        $this->logger->error('', ['exception' => $throwable]);
        echo($throwable);
    }
}