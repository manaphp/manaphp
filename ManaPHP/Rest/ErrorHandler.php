<?php
namespace ManaPHP\Rest;

use ManaPHP\Component;
use ManaPHP\ErrorHandlerInterface;

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
        if ($exception instanceof \ManaPHP\Exception) {
            $code = $exception->getStatusCode();
            $message = $exception->getStatusText();
        } else {
            $code = 500;
            $message = 'Internal Server Error';
        }

        if ($code >= 500) {
            $this->logger->error($exception);
        }

        $this->response->setStatus($code, $code === 200 ? 'OK' : $message);
        if ($this->configure->debug) {
            $this->response->setJsonContent(['code' => $code, 'message' => $message, 'exception' => explode("\n", $exception)]);
        } else {
            $this->response->setJsonContent(['code' => $code, 'message' => $message]);
        }
    }
}
