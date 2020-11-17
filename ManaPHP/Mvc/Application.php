<?php

namespace ManaPHP\Mvc;

use ManaPHP\Exception\AbortException;
use ManaPHP\Http\Response;
use Throwable;

/**
 * @property-read \ManaPHP\Mvc\ViewInterface $view
 */
class Application extends \ManaPHP\Http\Application
{
    public function getFactory()
    {
        return 'ManaPHP\Mvc\Factory';
    }

    public function authorize()
    {
        $this->authorization->authorize();
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
            } elseif ($actionReturnValue instanceof View) {
                $this->response->setContent($actionReturnValue->render());
                if (($maxAge = $actionReturnValue->getMaxAge()) > 0) {
                    $this->response->setMaxAge($maxAge);
                }
            } elseif (is_string($actionReturnValue)) {
                $this->response->setJsonError($actionReturnValue);
            } elseif (is_array($actionReturnValue)) {
                $this->response->setJsonData($actionReturnValue);
            } elseif (is_int($actionReturnValue)) {
                $this->response->setJsonError('', $actionReturnValue);
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
