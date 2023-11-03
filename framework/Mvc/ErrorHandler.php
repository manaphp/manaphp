<?php
declare(strict_types=1);

namespace ManaPHP\Mvc;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\Attribute\Config;
use ManaPHP\Exception;
use ManaPHP\Exception as ManaPHPException;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\ResponseInterface;
use ManaPHP\Rendering\RendererInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class ErrorHandler implements ErrorHandlerInterface
{
    #[Autowired] protected LoggerInterface $logger;
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ResponseInterface $response;
    #[Autowired] protected RendererInterface $renderer;

    #[Config] protected bool $app_debug;

    public function handle(Throwable $throwable): void
    {
        $code = $throwable instanceof Exception ? $throwable->getStatusCode() : 500;
        if ($code >= 500 && $code <= 599) {
            $this->logger->error('', ['exception' => $throwable]);
        }

        if ($this->request->isAjax()) {
            if ($throwable instanceof ManaPHPException) {
                $status = $throwable->getStatusCode();
                $json = $throwable->getJson();
            } else {
                $status = 500;
                $json = ['code' => $status, 'msg' => 'Internal Server Error'];
            }

            if ($this->app_debug) {
                $json['msg'] = $throwable::class . ': ' . $throwable->getMessage();
                $json['exception'] = explode("\n", (string)$throwable);
            }
            $this->response->json($json, $status);
        } else {
            $this->response->setStatus($code)->setContent($this->render($throwable));
        }
    }

    public function render(Throwable $exception): string
    {
        if ($this->app_debug) {
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