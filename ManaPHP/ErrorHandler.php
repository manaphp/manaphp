<?php
namespace ManaPHP;

use ManaPHP\Mvc\NotFoundException;
use ManaPHP\Renderer\Engine\Php;
use ManaPHP\Security\CsrfToken\Exception as CSrfTokenException;

/**
 * Class ManaPHP\ExceptionHandler
 *
 * @package ManaPHP
 *
 * @property \ManaPHP\Http\ResponseInterface $response
 * @property \ManaPHP\RendererInterface      $renderer
 */
class ErrorHandler extends Component implements ErrorHandlerInterface
{
    /**
     * @param \Exception|\ManaPHP\Exception $exception
     */
    public function handle($exception)
    {

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