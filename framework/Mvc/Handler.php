<?php
declare(strict_types=1);

namespace ManaPHP\Mvc;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Http\AbstractHandler;
use ManaPHP\Http\Response;
use Throwable;

class Handler extends AbstractHandler
{
    #[Autowired] protected ErrorHandlerInterface $errorHandler;

    protected function handleInternal(mixed $actionReturnValue): void
    {
        if ($actionReturnValue === null) {
            $this->response->setJsonOk();
        } elseif (\is_array($actionReturnValue)) {
            $this->response->setJsonData($actionReturnValue);
        } elseif ($actionReturnValue instanceof Response) {
            null;
        } elseif ($actionReturnValue instanceof View) {
            $this->response->setContent($actionReturnValue->render());
            if (($maxAge = $actionReturnValue->getMaxAge()) > 0) {
                $this->response->setMaxAge($maxAge);
            }
        } elseif (\is_string($actionReturnValue)) {
            $this->response->setJsonError($actionReturnValue);
        } elseif (\is_int($actionReturnValue)) {
            $this->response->setJsonError('', $actionReturnValue);
        } elseif ($actionReturnValue instanceof Throwable) {
            $this->response->setJsonThrowable($actionReturnValue);
        } else {
            $this->response->setJsonData($actionReturnValue);
        }
    }

    protected function handleError(Throwable $throwable): void
    {
        $this->errorHandler->handle($throwable);
    }
}