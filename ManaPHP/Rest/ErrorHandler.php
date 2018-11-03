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

            $json = $exception->getJson();
        } else {
            $code = 500;
            $message = 'Internal Server Error';

            $json = ['code' => $code, 'message' => $message];
        }

        if ($code !== 200) {
            $this->response->setStatus($code, $message);
        } elseif ($this->response->getContent() !== null) {
            return;
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
