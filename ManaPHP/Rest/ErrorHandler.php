<?php
declare(strict_types=1);

namespace ManaPHP\Rest;

use ManaPHP\Component;
use ManaPHP\Exception;
use Throwable;

/**
 * @property-read \ManaPHP\Logging\LoggerInterface $logger
 * @property-read \ManaPHP\Http\ResponseInterface  $response
 */
class ErrorHandler extends Component implements ErrorHandlerInterface
{
    public function handle(Throwable $throwable): void
    {
        $code = $throwable instanceof Exception ? $throwable->getCode() : 500;
        if ($code >= 500) {
            $this->logger->error($throwable);
        }

        $this->response->setJsonThrowable($throwable);
    }
}
