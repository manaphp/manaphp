<?php

namespace ManaPHP\Rest;

use ManaPHP\Component;
use ManaPHP\ErrorHandlerInterface;
use ManaPHP\Exception;

/**
 * Class ErrorHandler
 *
 * @package ManaPHP\Rest
 * @property-read \ManaPHP\Http\ResponseInterface $response
 */
class ErrorHandler extends Component implements ErrorHandlerInterface
{
    /**
     * @param \Throwable $throwable
     */
    public function handle($throwable)
    {
        if ($throwable instanceof Exception) {
            $code = $throwable->getStatusCode();
            $json = $throwable->getJson();

            if ($code !== 200) {
                $this->response->setStatus($code);
            } elseif ($this->response->getContent() !== '') {
                return;
            }
        } else {
            $code = 500;
            $json = ['code' => $code, 'message' => 'Internal Server Error'];
        }

        if ($code >= 500) {
            $this->logger->error($throwable);
        }

        if ($this->configure->debug) {
            $json['exception'] = explode("\n", $throwable);
        }

        $this->response->setStatus($code)->setJsonContent(json_stringify($json, JSON_INVALID_UTF8_SUBSTITUTE));
    }
}
