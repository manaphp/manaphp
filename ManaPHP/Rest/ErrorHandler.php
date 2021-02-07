<?php

namespace ManaPHP\Rest;

use ManaPHP\Component;
use ManaPHP\ErrorHandlerInterface;
use ManaPHP\Exception;

/**
 * @property-read \ManaPHP\Logging\LoggerInterface $logger
 * @property-read \ManaPHP\Http\ResponseInterface  $response
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
        $code = $throwable instanceof Exception ? $throwable->getCode() : 500;
        if ($code >= 500) {
            $this->logger->error($throwable);
        }

        $this->response->setJsonThrowable($throwable);
    }
}
