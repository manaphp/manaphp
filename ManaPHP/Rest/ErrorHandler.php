<?php
namespace ManaPHP\Rest;

use ManaPHP\Component;
use ManaPHP\ErrorHandlerInterface;

/**
 * Class ErrorHandler
 * @package ManaPHP\Rest
 * @property \ManaPHP\Http\ResponseInterface $response
 */
class ErrorHandler extends Component implements ErrorHandlerInterface
{
    /**
     * @param \Exception $exception
     */
    public function handle($exception)
    {
        if ($exception instanceof \ManaPHP\Exception) {
            if ($exception->getStatusCode() < 400) {
                return;
            }
            if ($exception->getStatusCode() === 500) {
                $this->logger->error($exception);
            }
            $this->response->setStatus($exception->getStatusCode(), $exception->getStatusText());
            $this->response->setJsonContent(['code' => $exception->getStatusCode(), 'message' => $exception->getStatusText()]);
        } else {
            $this->logger->error($exception);
            $this->response->setStatus(500, 'Internal Server Error');
            $this->response->setJsonContent(['code' => 500, 'message'=> 'Internal Server Error']);
        }
    }
}
