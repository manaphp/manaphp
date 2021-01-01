<?php

namespace ManaPHP\Mvc;

use ManaPHP\Component;
use ManaPHP\ErrorHandlerInterface;
use ManaPHP\Exception;

/**
 * @property-read \ManaPHP\Http\RequestInterface  $request
 * @property-read \ManaPHP\Http\ResponseInterface $response
 * @property-read \ManaPHP\Html\RendererInterface $renderer
 */
class ErrorHandler extends Component implements ErrorHandlerInterface
{
    /**
     * @param \Throwable $throwable
     *
     * @return void
     */
    public function handle($throwable)
    {
        $code = $throwable instanceof Exception ? $throwable->getCode() : 500;
        if ($code >= 500) {
            $this->logger->error($throwable);
        }

        if ($this->request->isAjax()) {
            $this->response->setJsonThrowable($throwable);
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