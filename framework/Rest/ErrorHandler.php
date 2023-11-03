<?php
declare(strict_types=1);

namespace ManaPHP\Rest;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\Attribute\Config;
use ManaPHP\Exception;
use ManaPHP\Exception as ManaPHPException;
use ManaPHP\Http\ResponseInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class ErrorHandler implements ErrorHandlerInterface
{
    #[Autowired] protected LoggerInterface $logger;
    #[Autowired] protected ResponseInterface $response;

    #[Config] protected bool $app_debug;

    public function handle(Throwable $throwable): void
    {
        $code = $throwable instanceof Exception ? $throwable->getCode() : 500;
        if ($code >= 500 && $code <= 599) {
            $this->logger->error('', ['exception' => $throwable]);
        }

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
    }
}
