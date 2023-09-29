<?php
declare(strict_types=1);

namespace ManaPHP\Rest;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception;
use ManaPHP\Http\ResponseInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class ErrorHandler implements ErrorHandlerInterface
{
    #[Autowired] protected LoggerInterface $logger;
    #[Autowired] protected ResponseInterface $response;

    public function handle(Throwable $throwable): void
    {
        $code = $throwable instanceof Exception ? $throwable->getCode() : 500;
        if ($code >= 500) {
            $this->logger->error('', ['exception' => $throwable]);
        }

        $this->response->setJsonThrowable($throwable);
    }
}
