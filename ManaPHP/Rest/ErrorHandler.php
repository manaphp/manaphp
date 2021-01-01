<?php

namespace ManaPHP\Rest;

use ManaPHP\Component;
use ManaPHP\ErrorHandlerInterface;
use ManaPHP\Exception;

/**
 * @property-read \ManaPHP\Http\ResponseInterface $response
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
        if ($throwable instanceof Exception) {
            $code = $throwable->getStatusCode();

            if ($code !== 200) {
                $this->response->setStatus($code);
            } elseif ($this->response->getContent() !== '') {
                return;
            }
        } else {
            $code = 500;
        }

        if ($code >= 500) {
            $this->logger->error($throwable);
        }

        $this->response->setJsonThrowable($throwable);
    }
}
