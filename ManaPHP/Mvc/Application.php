<?php

namespace ManaPHP\Mvc;

use ManaPHP\Http\Response;
use ManaPHP\View;

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
            $this->_di->setShared('identity', 'ManaPHP\Identity\Adapter\Session');
        }
        return $this->_di;
    }

    public function authorize()
    {
        $this->authorization->authorize();
    }

    public function handle()
    {
        try {
            $this->eventsManager->fireEvent('request:begin', $this);
            $this->eventsManager->fireEvent('request:construct', $this);

            $this->eventsManager->fireEvent('request:authenticate', $this);

            $actionReturnValue = $this->router->dispatch();
            if ($actionReturnValue === null || $actionReturnValue instanceof View) {
                $this->response->setContent($this->view->render());
            } elseif ($actionReturnValue instanceof Response) {
                null;
            } elseif (is_string($actionReturnValue)) {
                $this->response->setJsonError($actionReturnValue);
            } else {
                $this->response->setJsonContent($actionReturnValue);
            }
        } catch (\Exception $e) {
            $this->handleException($e);
        } catch (\Error $e) {
            $this->handleException($e);
        }

        $this->send();

        $this->eventsManager->fireEvent('request:destruct', $this);
        $this->eventsManager->fireEvent('request:end', $this);
    }
}