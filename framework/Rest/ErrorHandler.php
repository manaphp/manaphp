<?php
declare(strict_types=1);

namespace ManaPHP\Rest;

use ManaPHP\Component;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Exception;
use ManaPHP\Http\ResponseInterface;
use ManaPHP\Logging\LoggerInterface;
use Throwable;

class ErrorHandler extends Component implements ErrorHandlerInterface
{
    #[Inject] protected LoggerInterface $logger;
    #[Inject] protected ResponseInterface $response;

    public function handle(Throwable $throwable): void
    {
        $code = $throwable instanceof Exception ? $throwable->getCode() : 500;
        if ($code >= 500) {
            $this->logger->error($throwable);
        }

        $this->response->setJsonThrowable($throwable);
    }
}
