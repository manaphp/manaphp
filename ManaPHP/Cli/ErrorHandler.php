<?php

namespace ManaPHP\Cli;

use ManaPHP\Component;
use ManaPHP\ErrorHandlerInterface;

/**
 * @property-read \ManaPHP\Logging\LoggerInterface $logger
 */
class ErrorHandler extends Component implements ErrorHandlerInterface
{
    /**
     * @param \Throwable $throwable
     *
     * @return void
     */
    public function handle($throwable)
    {
        $this->logger->error($throwable);
        echo($throwable);
    }
}