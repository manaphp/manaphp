<?php
declare(strict_types=1);

namespace ManaPHP\Mvc;

use ManaPHP\Component;
use ManaPHP\Exception\AbortException;
use ManaPHP\Http\HandlerInterface;
use ManaPHP\Http\Response;
use ManaPHP\Http\Router\NotFoundRouteException;
use Throwable;

/**
 * @property-read \ManaPHP\Http\ResponseInterface    $response
 * @property-read \ManaPHP\Http\RouterInterface      $router
 * @property-read \ManaPHP\Http\DispatcherInterface  $dispatcher
 * @property-read \ManaPHP\Mvc\ErrorHandlerInterface $errorHandler
 * @property-read \ManaPHP\Http\ServerInterface      $httpServer
 */
class Handler extends Component implements HandlerInterface
{
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
            $this->fireEvent('request:exception', compact('exception'));

            $this->errorHandler->handle($exception);
        }

        $this->httpServer->send();

        $this->fireEvent('request:end');
    }
}