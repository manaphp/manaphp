<?php
declare(strict_types=1);

namespace ManaPHP\Ws;

use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Exception\AbortException;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\Response;
use ManaPHP\Http\ResponseInterface;
use ManaPHP\Http\Router\NotFoundRouteException;
use ManaPHP\Http\RouterInterface;
use ManaPHP\Http\Server\Event\RequestAuthenticated;
use ManaPHP\Http\Server\Event\RequestAuthenticating;
use ManaPHP\Http\Server\Event\RequestBegin;
use ManaPHP\Http\Server\Event\RequestEnd;
use ManaPHP\Identifying\IdentityInterface;
use ManaPHP\Ws\Server\Event\Close;
use ManaPHP\Ws\Server\Event\Open;
use Psr\EventDispatcher\EventDispatcherInterface;
use Throwable;

class Handler implements HandlerInterface
{
    #[Inject] protected EventDispatcherInterface $eventDispatcher;
    #[Inject] protected ServerInterface $wsServer;
    #[Inject] protected IdentityInterface $identity;
    #[Inject] protected RouterInterface $router;
    #[Inject] protected RequestInterface $request;
    #[Inject] protected ResponseInterface $response;
    #[Inject] protected DispatcherInterface $dispatcher;
    #[Inject] protected ErrorHandlerInterface $errorHandler;

    /**
     * @noinspection PhpRedundantCatchClauseInspection
     * @noinspection PhpUnusedLocalVariableInspection
     */
    public function handle(int $fd, string $event): void
    {
        try {
            $throwable = null;

            $this->eventDispatcher->dispatch(new RequestBegin());

            if ($event === 'open') {
                $this->eventDispatcher->dispatch(new RequestAuthenticating());
                $this->eventDispatcher->dispatch(new RequestAuthenticated());
            }

            if (!$this->router->match()) {
                throw new NotFoundRouteException(['router does not have matched route']);
            }

            $this->router->setAction($event);

            $returnValue = $this->dispatcher->dispatch(
                $this->router->getArea(), $this->router->getController(), $this->router->getAction(),
                $this->router->getParams()
            );

            if ($returnValue === null || $returnValue instanceof Response) {
                null;
            } elseif (is_string($returnValue)) {
                $this->response->setJsonError($returnValue);
            } elseif (is_array($returnValue)) {
                $this->response->setJsonData($returnValue);
            } elseif (is_int($returnValue)) {
                $this->response->setJsonError('', $returnValue);
            } else {
                $this->response->setJsonContent($returnValue);
            }

            if ($event === 'open') {
                $this->eventDispatcher->dispatch(new Open($fd));
            } elseif ($event === 'close') {
                $this->eventDispatcher->dispatch(new Close($fd));
            }
        } catch (AbortException $exception) {
            null;
        } catch (Throwable $throwable) {
            $this->errorHandler->handle($throwable);
        }

        if ($content = $this->response->getContent()) {
            $this->wsServer->push($fd, $content);
        }

        $this->eventDispatcher->dispatch(new RequestEnd($this->request, $this->response));

        if ($throwable) {
            $this->wsServer->disconnect($fd);
        }
    }

    public function onOpen(int $fd): void
    {
        $this->handle($fd, 'open');
    }

    public function onClose(int $fd): void
    {
        $this->handle($fd, 'close');
    }

    public function onMessage(int $fd, string $data): void
    {
        $this->request->set('data', $data);
        $this->handle($fd, 'message');
        $this->request->delete('data');
    }
}