<?php
namespace ManaPHP\Mvc;

use ManaPHP\Component;
use ManaPHP\ErrorHandlerInterface;

/**
 * Class ManaPHP\Mvc\ErrorHandler
 *
 * @package ManaPHP\Mvc
 * @property \ManaPHP\Http\RequestInterface  $request
 * @property \ManaPHP\Http\ResponseInterface $response
 * @property \ManaPHP\RendererInterface      $renderer
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
            $this->response->setStatus($exception->getStatusCode(), $exception->getStatusText());
            if ($exception->getStatusCode() === 500) {
                $this->logger->error($exception);
            }
        } else {
            $this->response->setStatus(500, 'Internal Server Error');
            $this->logger->error($exception);
        }

        $this->response->setContent($this->render($exception));
    }

    /**
     * @param \Exception|\ManaPHP\Exception $exception
     *
     * @return string
     */
    public function render($exception)
    {
        for ($level = ob_get_level(); $level > 0; $level--) {
            ob_end_clean();
        }

        if ($this->configure->debug) {
            if ($this->renderer->exists('@views/Errors/debug')) {
                return $this->renderer->render('@views/Errors/debug', ['exception' => $exception]);
            } else {
                return $this->renderer->render('@manaphp/Mvc/ErrorHandler/Views/debug', ['exception' => $exception]);
            }
        }

        $statusCode = $exception instanceof \ManaPHP\Exception ? $exception->getStatusCode() : 500;

        foreach (["@views/Errors/$statusCode",
                     '@views/Errors/error'] as $template) {
            if ($this->renderer->exists($template)) {
                return $this->renderer->render($template, ['statusCode' => $statusCode, 'exception' => $exception]);
            }
        }
        $statusText = $exception instanceof \ManaPHP\Exception ? $exception->getStatusText() : 'App Error';
        return "<html><title>$statusCode: $statusText</title><body>$statusCode: $statusText</body></html>";
    }
}