<?php
namespace ManaPHP\Mvc;

use ManaPHP\Component;
use ManaPHP\ErrorHandlerInterface;
use ManaPHP\Exception;

/**
 * Class ManaPHP\Mvc\ErrorHandler
 *
 * @package ManaPHP\Mvc
 * @property-read \ManaPHP\Http\RequestInterface  $request
 * @property-read \ManaPHP\Http\ResponseInterface $response
 * @property-read \ManaPHP\RendererInterface      $renderer
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

        if ($this->request->isAjax()) {
            if ($this->configure->debug) {
                $json['exception'] = explode("\n", $exception);
            }
            $this->response->setJsonContent($json);
        } else {
            $this->response->setContent($this->render($exception));
        }
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

        $statusCode = $exception instanceof Exception ? $exception->getStatusCode() : 500;

        foreach (["@views/Errors/$statusCode",
                     '@views/Errors/error'] as $template) {
            if ($this->renderer->exists($template)) {
                return $this->renderer->render($template, ['statusCode' => $statusCode, 'exception' => $exception]);
            }
        }
        $status = $this->response->getStatus();
        return "<html lang='en'><title>$status</title><body>$status</body></html>";
    }
}