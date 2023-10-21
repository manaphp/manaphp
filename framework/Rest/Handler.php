<?php
declare(strict_types=1);

namespace ManaPHP\Rest;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception\AbortException;
use ManaPHP\Http\AbstractHandler;
use ManaPHP\Http\Response;
use ManaPHP\Http\Router\NotFoundRouteException;
use ManaPHP\Http\Server\Event\RequestAuthenticated;
use ManaPHP\Http\Server\Event\RequestAuthenticating;
use ManaPHP\Http\Server\Event\RequestBegin;
use ManaPHP\Http\Server\Event\RequestEnd;
use ManaPHP\Http\Server\Event\RequestException;
use Throwable;

class Handler extends AbstractHandler
{
    #[Autowired] protected ErrorHandlerInterface $errorHandler;

    /**
     * @noinspection PhpRedundantCatchClauseInspection
     * @noinspection PhpUnusedLocalVariableInspection
     */
    public function handle(): void
    {
        try {
            $this->eventDispatcher->dispatch(new RequestBegin($this->request));

            $this->eventDispatcher->dispatch(new RequestAuthenticating());
            $this->eventDispatcher->dispatch(new RequestAuthenticated());

            if (!$this->router->match()) {
                throw new NotFoundRouteException(
                    ['router does not have matched route for `{1}`', $this->router->getRewriteUri()]
                );
            }

            $actionReturnValue = $this->dispatcher->dispatch(
                $this->router->getArea(), $this->router->getController(), $this->router->getAction(),
                $this->router->getParams()
            );

            if ($actionReturnValue === null) {
                $this->response->setJsonOk();
            } elseif (is_array($actionReturnValue)) {
                $this->response->setJsonData($actionReturnValue);
            } elseif ($actionReturnValue instanceof Response) {
                null;
            } elseif (is_string($actionReturnValue)) {
                $this->response->setJsonError($actionReturnValue);
            } elseif (is_int($actionReturnValue)) {
                $this->response->setJsonError('', $actionReturnValue);
            } elseif ($actionReturnValue instanceof Throwable) {
                $this->response->setJsonThrowable($actionReturnValue);
            } else {
                $this->response->setJsonData($actionReturnValue);
            }
        } catch (AbortException $exception) {
            null;
        } catch (Throwable $exception) {
            $this->eventDispatcher->dispatch(new RequestException($exception));

            $this->errorHandler->handle($exception);
        }

        $this->httpServer->send();

        $this->accessLog->log();

        $this->eventDispatcher->dispatch(new RequestEnd($this->request, $this->response));
    }
}