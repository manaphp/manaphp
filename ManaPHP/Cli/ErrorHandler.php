<?php

namespace ManaPHP\Cli;

use ManaPHP\Component;
use ManaPHP\ErrorHandlerInterface;

class ErrorHandler extends Component implements ErrorHandlerInterface
{
    /**
     * @param \Throwable $throwable
     */
    public function handle($throwable)
    {
        $this->logger->error($throwable);
        echo($throwable);
    }
}