<?php
namespace ManaPHP\Cli;

use ManaPHP\Component;
use ManaPHP\ErrorHandlerInterface;
use ManaPHP\Exception\AbortException;

class ErrorHandler extends Component implements ErrorHandlerInterface
{
    /**
     * @param \Exception|\ManaPHP\Exception $exception
     */
    public function handle($exception)
    {
        $this->logger->error($exception);
        echo($exception);
    }
}