<?php
namespace ManaPHP;

use ManaPHP\Mvc\NotFoundException;
use ManaPHP\Security\CsrfToken\Exception as CSrfTokenException;

/**
 * Class ManaPHP\ExceptionHandler
 *
 * @package ManaPHP
 *
 * @property \ManaPHP\Http\ResponseInterface $response
 */
class ErrorHandler extends Component implements ErrorHandlerInterface
{
    /**
     * @param \Exception $exception
     */
    public function handleException($exception)
    {
        if ($exception instanceof NotFoundException) {
            $this->response->setStatusCode('404', 'Not Found');
            $this->response->setContent('');
            return;
        } elseif ($exception instanceof CSrfTokenException) {
            $this->response->setStatusCode('403', 'Forbidden');
            $this->response->setContent('');
            return;
        }

        $this->logException($exception);

        $this->response->setStatusCode(500, 'Internal Server Error');
        $this->response->setContent('');
    }

    /**
     * @param \Exception $exception
     *
     * @return array
     */
    public function getLogData($exception)
    {
        $data = [];

        $data['class'] = get_class($exception);
        $data['message'] = $exception->getMessage();
        $data['code'] = $exception->getCode();
        $data['location'] = $exception->getFile() . ':' . $exception->getLine();
        $traces = explode('#', $exception->getTraceAsString());
        unset($traces[0]);
        $data['traces'] = array_values($traces);

        return $data;
    }

    /**
     * @param \Exception $exception
     */
    public function logException($exception)
    {
        $this->logger->error(json_encode($this->getLogData($exception), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}