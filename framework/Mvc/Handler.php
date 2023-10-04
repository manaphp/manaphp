<?php
declare(strict_types=1);

namespace ManaPHP\Mvc;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception\AbortException;
use ManaPHP\Http\DispatcherInterface;
use ManaPHP\Http\HandlerInterface;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\Response;
use ManaPHP\Http\ResponseInterface;
use ManaPHP\Http\Router\NotFoundRouteException;
use ManaPHP\Http\RouterInterface;
use ManaPHP\Http\Server\Event\RequestAuthenticated;
use ManaPHP\Http\Server\Event\RequestAuthenticating;
use ManaPHP\Http\Server\Event\RequestBegin;
use ManaPHP\Http\Server\Event\RequestEnd;
use ManaPHP\Http\Server\Event\RequestException;
use ManaPHP\Http\ServerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Throwable;

class Handler implements HandlerInterface
{
    #[Autowired] protected EventDispatcherInterface $eventDispatcher;
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ResponseInterface $response;
    #[Autowired] protected RouterInterface $router;
    #[Autowired] protected DispatcherInterface $dispatcher;
    #[Autowired] protected ErrorHandlerInterface $errorHandler;
    #[Autowired] protected ServerInterface $httpServer;

    /**
     * @noinspection PhpRedundantCatchClauseInspection
     * @noinspection PhpUnusedLocalVariableInspection
     */
    public function handle(): void
    {
        try {
            $this->eventDispatcher->dispatch(new RequestBegin());

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
            } elseif ($actionReturnValue instanceof View) {
                $this->response->setContent($actionReturnValue->render());
                if (($maxAge = $actionReturnValue->getMaxAge()) > 0) {
                    $this->response->setMaxAge($maxAge);
                }
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

        $this->eventDispatcher->dispatch(new RequestEnd($this->request, $this->response));
    }
}