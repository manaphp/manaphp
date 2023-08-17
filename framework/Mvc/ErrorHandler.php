<?php
declare(strict_types=1);

namespace ManaPHP\Mvc;

use ManaPHP\ConfigInterface;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Exception;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\ResponseInterface;
use ManaPHP\Logging\LoggerInterface;
use ManaPHP\Rendering\RendererInterface;
use Throwable;

class ErrorHandler implements ErrorHandlerInterface
{
    #[Inject] protected ConfigInterface $config;
    #[Inject] protected LoggerInterface $logger;
    #[Inject] protected RequestInterface $request;
    #[Inject] protected ResponseInterface $response;
    #[Inject] protected RendererInterface $renderer;

    public function handle(Throwable $throwable): void
    {
        $code = $throwable instanceof Exception ? $throwable->getStatusCode() : 500;
        if ($code >= 500) {
            $this->logger->error($throwable);
        }

        if ($this->request->isAjax()) {
            $this->response->setJsonThrowable($throwable);
        } else {
            $this->response->setStatus($code)->setContent($this->render($throwable));
        }
    }

    public function render(Throwable $exception): string
    {
        if ($this->config->get('debug')) {
            if ($this->renderer->exists('@views/Errors/Debug')) {
                $template = '@views/Errors/Debug';
            } else {
                $template = '@manaphp/Mvc/ErrorHandler/Views/Debug';
            }
            return $this->renderer->renderFile($template, ['exception' => $exception]);
        }

        $statusCode = $exception instanceof Exception ? $exception->getStatusCode() : 500;

        foreach (
            [
                "@views/Errors/$statusCode",
                '@views/Errors/Error'
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