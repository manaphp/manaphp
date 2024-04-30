<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Eventing\EventDispatcherInterface;
use ManaPHP\Eventing\ListenerProviderInterface;
use ManaPHP\Exception\AbortException;
use ManaPHP\Helper\SuppressWarnings;
use ManaPHP\Http\Router\NotFoundRouteException;
use ManaPHP\Http\Server\Event\RequestAuthenticated;
use ManaPHP\Http\Server\Event\RequestAuthenticating;
use ManaPHP\Http\Server\Event\RequestBegin;
use ManaPHP\Http\Server\Event\RequestEnd;
use ManaPHP\Http\Server\Event\RequestException;
use Throwable;

abstract class AbstractHandler implements HandlerInterface
{
    #[Autowired] protected EventDispatcherInterface $eventDispatcher;
    #[Autowired] protected ListenerProviderInterface $listenerProvider;
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ResponseInterface $response;
    #[Autowired] protected RouterInterface $router;
    #[Autowired] protected DispatcherInterface $dispatcher;
    #[Autowired] protected AccessLogInterface $accessLog;
    #[Autowired] protected ServerInterface $httpServer;

    #[Autowired] protected array $middlewares = [];

    public function __construct()
    {
        foreach ($this->middlewares as $middleware) {
            if ($middleware !== '' && $middleware !== null) {
                $this->listenerProvider->add($middleware);
            }
        }
    }

    abstract protected function handleInternal(mixed $actionReturnValue): void;

    abstract protected function handleError(Throwable $throwable): void;

    /** @noinspection PhpRedundantCatchClauseInspection */
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

            $this->handleInternal($actionReturnValue);
        } catch (AbortException) {
            SuppressWarnings::noop();
        } catch (Throwable $exception) {
            $this->eventDispatcher->dispatch(new RequestException($exception));
            $this->handleError($exception);
        }

        $this->httpServer->send();

        $this->accessLog->log();

        $this->eventDispatcher->dispatch(new RequestEnd($this->request, $this->response));
    }
}