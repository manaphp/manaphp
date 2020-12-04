<?php

namespace ManaPHP\Cli;

use ManaPHP\Component;
use ManaPHP\ErrorHandlerInterface;

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