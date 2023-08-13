<?php
declare(strict_types=1);

namespace ManaPHP\Rest;

use ManaPHP\Component;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Event\EventTrait;
use ManaPHP\Exception\AbortException;
use ManaPHP\Http\DispatcherInterface;
use ManaPHP\Http\HandlerInterface;
use ManaPHP\Http\Response;
use ManaPHP\Http\ResponseInterface;
use ManaPHP\Http\Router\NotFoundRouteException;
use ManaPHP\Http\RouterInterface;
use ManaPHP\Http\ServerInterface;
use Throwable;

class Handler extends Component implements HandlerInterface
{
    use EventTrait;

    #[Inject] protected ResponseInterface $response;
    #[Inject] protected RouterInterface $router;
    #[Inject] protected DispatcherInterface $dispatcher;
    #[Inject] protected ErrorHandlerInterface $errorHandler;
    #[Inject] protected ServerInterface $httpServer;

    /**
     * @noinspection PhpRedundantCatchClauseInspection
     * @noinspection PhpUnusedLocalVariableInspection
     */
    public function handle(): void
    {
        try {
            $this->fireEvent('request:begin');

            $this->fireEvent('request:authenticating');
            $this->fireEvent('request:authenticated');

            if (!$this->router->match()) {
                throw new NotFoundRouteException(
                    ['router does not have matched route for `%s`', $this->router->getRewriteUri()]
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
            $this->fireEvent('request:exception', compact('exception'));

            $this->errorHandler->handle($exception);
        }

        $this->httpServer->send();

        $this->fireEvent('request:end');
    }
}