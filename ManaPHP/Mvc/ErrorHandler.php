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
 * @property-read \ManaPHP\Html\RendererInterface $renderer
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

        if ($this->request->isAjax()) {
            if ($this->configure->debug) {
                $json['exception'] = explode("\n", $throwable);
            }
            $this->response->setStatus($code)->setJsonContent(json_stringify($json, JSON_INVALID_UTF8_SUBSTITUTE));
        } else {
            $this->response->setStatus($code)->setContent($this->render($throwable));
        }
    }

    /**
     * @param \Exception|\ManaPHP\Exception $exception
     *
     * @return string
     */
    public function render($exception)
    {
        if ($this->configure->debug) {
            if ($this->renderer->exists('@views/Errors/debug')) {
                $template = '@views/Errors/debug';
            } else {
                $template = '@manaphp/Mvc/ErrorHandler/Views/debug';
            }
            return $this->renderer->renderFile($template, ['exception' => $exception]);
        }

        $statusCode = $exception instanceof Exception ? $exception->getStatusCode() : 500;

        foreach (
            [
                "@views/Errors/$statusCode",
                '@views/Errors/error'
            ] as $template
        ) {
            if ($this->renderer->exists($template)) {
                return $this->renderer->renderFile($template, ['statusCode' => $statusCode, 'exception' => $exception]);
            }
        }
        $status = $this->response->getStatus();
        return "<html lang='en'><title>$status</title><body>$status</body></html>";
    }
}