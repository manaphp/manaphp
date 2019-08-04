<?php
namespace ManaPHP\Rest;

use ManaPHP\Component;
use ManaPHP\ErrorHandlerInterface;
use ManaPHP\Exception;

/**
 * Class ErrorHandler
 * @package ManaPHP\Rest
 * @property-read \ManaPHP\Http\ResponseInterface $response
 */
class ErrorHandler extends Component implements ErrorHandlerInterface
{
    /**
     * @param \Exception $exception
     */
    public function handle($exception)
    {
        if ($exception instanceof Exception) {
            $code = $exception->getStatusCode();
            $json = $exception->getJson();

            if ($code !== 200) {
                $this->response->setStatus($code);
            } elseif ($this->response->getContent() !== null) {
                return;
            }
        } else {
            $code = 500;
            $json = ['code' => $code, 'message' => 'Internal Server Error'];
        }

        if ($code >= 500) {
            $this->logger->error($exception);
        }

        if ($this->configure->debug) {
            $json['exception'] = explode("\n", $exception);
        }

        $this->response->setJsonContent($json);
    }
}
