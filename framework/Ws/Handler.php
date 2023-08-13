<?php
declare(strict_types=1);

namespace ManaPHP\Ws;

use ManaPHP\Component;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Event\EventTrait;
use ManaPHP\Exception\AbortException;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Http\Response;
use ManaPHP\Http\ResponseInterface;
use ManaPHP\Http\Router\NotFoundRouteException;
use ManaPHP\Http\RouterInterface;
use ManaPHP\Identifying\IdentityInterface;
use Throwable;

class Handler extends Component implements HandlerInterface
{
    use EventTrait;

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

            $this->fireEvent('request:begin');

            if ($event === 'open') {
                $this->fireEvent('request:authenticating');
                $this->fireEvent('request:authenticated');
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
                $this->fireEvent('wsServer:open', compact('fd'));
            } elseif ($event === 'close') {
                $this->fireEvent('wsServer:close', compact('fd'));
            }
        } catch (AbortException $exception) {
            null;
        } catch (Throwable $throwable) {
            $this->errorHandler->handle($throwable);
        }

        if ($content = $this->response->getContent()) {
            $this->wsServer->push($fd, $content);
        }

        $this->fireEvent('request:end');

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