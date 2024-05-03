<?php
declare(strict_types=1);

namespace ManaPHP\Ws;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception\AbortException;
use ManaPHP\Helper\SuppressWarnings;
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
use function is_array;
use function is_int;
use function is_string;

class Handler implements HandlerInterface
{
    #[Autowired] protected EventDispatcherInterface $eventDispatcher;
    #[Autowired] protected ServerInterface $wsServer;
    #[Autowired] protected IdentityInterface $identity;
    #[Autowired] protected RouterInterface $router;
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ResponseInterface $response;
    #[Autowired] protected DispatcherInterface $dispatcher;
    #[Autowired] protected ErrorHandlerInterface $errorHandler;

    /**
     * @noinspection PhpRedundantCatchClauseInspection
     * @noinspection PhpUnusedLocalVariableInspection
     */
    public function handle(int $fd, string $event): void
    {
        try {
            $throwable = null;

            $this->eventDispatcher->dispatch(new RequestBegin($this->request));

            if ($event === 'open') {
                $this->eventDispatcher->dispatch(new RequestAuthenticating());
                $this->eventDispatcher->dispatch(new RequestAuthenticated());
            }

            if (($matcher = $this->router->match()) === null) {
                throw new NotFoundRouteException(['router does not have matched route']);
            }

            $returnValue = $this->dispatcher->dispatch(
                $matcher->getHandler(), $matcher->getParams()
            );

            if ($returnValue === null || $returnValue instanceof Response) {
                SuppressWarnings::noop();
            } elseif (is_string($returnValue)) {
                $this->response->json(['code' => $returnValue, 'msg' => '']);
            } elseif (is_array($returnValue)) {
                $this->response->json(['code' => 0, 'msg' => '', 'data' => $returnValue]);
            } elseif (is_int($returnValue)) {
                $this->response->json(['code' => $returnValue, 'msg' => '']);
            } else {
                $this->response->json($returnValue);
            }

            if ($event === 'open') {
                $this->eventDispatcher->dispatch(new Open($fd));
            } elseif ($event === 'close') {
                $this->eventDispatcher->dispatch(new Close($fd));
            }
        } catch (AbortException $exception) {
            SuppressWarnings::noop();
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