<?php
declare(strict_types=1);

namespace ManaPHP\Ws;

use ManaPHP\Component;
use ManaPHP\Exception\AbortException;
use ManaPHP\Http\Response;
use ManaPHP\Http\Router\NotFoundRouteException;
use Throwable;

/**
 * @property-read \ManaPHP\Ws\ServerInterface            $wsServer
 * @property-read \ManaPHP\Identifying\IdentityInterface $identity
 * @property-read \ManaPHP\Http\RouterInterface          $router
 * @property-read \ManaPHP\Http\RequestInterface         $request
 * @property-read \ManaPHP\Http\ResponseInterface        $response
 * @property-read \ManaPHP\Ws\DispatcherInterface        $dispatcher
 * @property-read \ManaPHP\Ws\ErrorHandlerInterface      $errorHandler
 */
class Handler extends Component implements HandlerInterface
{
    /** @noinspection PhpRedundantCatchClauseInspection */
    public function handle(int $fd, string $event): void
    {
        try {
            $throwable = null;

            $this->fireEvent('request:begin');

            if ($event === 'open') {
                $this->fireEvent('request:authenticate');
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