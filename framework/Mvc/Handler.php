<?php
declare(strict_types=1);

namespace ManaPHP\Mvc;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Helper\SuppressWarnings;
use ManaPHP\Http\AbstractHandler;
use ManaPHP\Http\Response;
use Throwable;
use function is_array;
use function is_int;
use function is_string;

class Handler extends AbstractHandler
{
    #[Autowired] protected ErrorHandlerInterface $errorHandler;

    protected function handleInternal(mixed $actionReturnValue): void
    {
        if ($actionReturnValue === null) {
            $this->response->json(['code' => 0, 'msg' => '']);
        } elseif (is_array($actionReturnValue)) {
            $this->response->json(['code' => 0, 'msg' => '', 'data' => $actionReturnValue]);
        } elseif ($actionReturnValue instanceof Response) {
            SuppressWarnings::noop();
        } elseif ($actionReturnValue instanceof View) {
            $this->response->setContent($actionReturnValue->render());
            if (($maxAge = $actionReturnValue->getMaxAge()) > 0) {
                $this->response->setMaxAge($maxAge);
            }
        } elseif (is_string($actionReturnValue)) {
            $this->response->json(['code' => -1, 'msg' => $actionReturnValue]);
        } elseif (is_int($actionReturnValue)) {
            $this->response->json(['code' => $actionReturnValue, 'msg' => '']);
        } elseif ($actionReturnValue instanceof Throwable) {
            $this->handleError($actionReturnValue);
        } else {
            $this->response->json(['code' => 0, 'msg' => '', 'data' => $actionReturnValue]);
        }
    }

    protected function handleError(Throwable $throwable): void
    {
        $this->errorHandler->handle($throwable);
    }
}