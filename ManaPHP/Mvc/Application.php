<?php

namespace ManaPHP\Mvc;

use ManaPHP\Http\Response;
use ManaPHP\View;
use Swoole\Runtime;

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
            $this->eventsManager->fireEvent('request:begin', $this);
            $this->eventsManager->fireEvent('request:construct', $this);

            $this->eventsManager->fireEvent('request:authenticate', $this);

            $actionReturnValue = $this->router->dispatch();
            if ($actionReturnValue === null || $actionReturnValue instanceof View) {
                $this->view->render();
                $this->response->setContent($this->view->getContent());
            } elseif ($actionReturnValue instanceof Response) {
                null;
            } elseif ($this->dispatcher->getControllerInstance() instanceof \ManaPHP\Rest\Controller) {
                $this->response->setJsonContent($actionReturnValue);
            } else {
                $this->response->setContent($actionReturnValue);
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