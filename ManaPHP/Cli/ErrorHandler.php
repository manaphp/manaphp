<?php
declare(strict_types=1);

namespace ManaPHP\Cli;

use ManaPHP\Component;
use Throwable;

/**
 * @property-read \ManaPHP\Logging\LoggerInterface $logger
 */
class ErrorHandler extends Component implements ErrorHandlerInterface
{
    public function handle(Throwable $throwable): void
    {
        $this->logger->error($throwable);
        echo($throwable);
    }
}