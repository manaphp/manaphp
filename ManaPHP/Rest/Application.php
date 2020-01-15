<?php

namespace ManaPHP\Rest;

use ManaPHP\Exception\AbortException;
use ManaPHP\Http\Response;
use Throwable;

/**
 * Class ManaPHP\Mvc\Application
 *
 * @package application
 */
class Application extends \ManaPHP\Http\Application
{
    public function getDi()
    {
        if (!$this->_di) {
            $this->_di = new Factory();
        }

        return $this->_di;
    }

    public function handle()
    {
        try {
            $this->fireEvent('request:begin');

            $this->fireEvent('request:authenticate');

            $actionReturnValue = $this->router->dispatch();

            if ($actionReturnValue === null) {
                $this->response->setJsonOk();
            } elseif ($actionReturnValue instanceof Response) {
                null;
            } elseif (is_string($actionReturnValue)) {
                $this->response->setJsonError($actionReturnValue);
            } else {
                $this->response->setJsonContent($actionReturnValue);
            }
        } catch (AbortException $exception) {
            null;
        } catch (Throwable $throwable) {
            $this->fireEvent('request:exception', $throwable);

            $this->handleException($throwable);
        }

        $response = $this->response->getContext();
        if (!$response->file && !is_string($response->content)) {
            $response->content = json_stringify($response->content);
        }

        $this->httpServer->send($response);

        $this->fireEvent('request:end');
    }
}
