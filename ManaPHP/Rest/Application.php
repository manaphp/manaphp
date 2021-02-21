<?php

namespace ManaPHP\Rest;

use ManaPHP\Exception\AbortException;
use ManaPHP\Helper\Reflection;
use ManaPHP\Http\Response;
use Throwable;

class Application extends \ManaPHP\Http\Application
{
    /**
     * @return string
     */
    public function getFactory()
    {
        return 'ManaPHP\Rest\Factory';
    }

    /**
     * @return void
     */
    public function handle()
    {
        try {
            $this->fireEvent('request:begin');

            $this->fireEvent('request:authenticate');

            $actionReturnValue = $this->router->dispatch();

            if ($actionReturnValue === null) {
                $this->response->setJsonOk();
            } elseif (is_array($actionReturnValue)) {
                $this->response->setJsonData($actionReturnValue);
            } elseif (Reflection::isInstanceOf($actionReturnValue, Response::class)) {
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

            $this->handleException($exception);
        }

        $this->httpServer->send();

        $this->fireEvent('request:end');
    }
}
