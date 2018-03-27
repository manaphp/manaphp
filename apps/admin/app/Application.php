<?php
namespace App\Admin;

use ManaPHP\Authentication\UserIdentity;
use ManaPHP\Authorization\Rbac;

if (PHP_EOL === "\n") {
    require __DIR__ . '/../vendor/manaphp/framework/Mvc/Url/helpers.php';
} else {
    require __DIR__ . '/../../../ManaPHP/Mvc/Url/helpers.php';
}

class Application extends \ManaPHP\Mvc\Application
{
    public function authenticate()
    {
        $this->_di->authorization = new Rbac();
        $this->_di->userIdentity = new UserIdentity($this->session->get('admin_auth', []));
    }

    /**
     * @param \ManaPHP\Mvc\DispatcherInterface $dispatcher
     *
     * @return true|\ManaPHP\Http\ResponseInterface
     */
    public function authorize($dispatcher)
    {
        if ($dispatcher->getActionName() === 'captcha') {
            return true;
        }

        if (strpos('User/', $dispatcher->getControllerName()) === 0) {
            if (in_array($dispatcher->getControllerName(), ['Session', 'User'], true)) {
                return true;
            }
        }

        if (!$this->userIdentity->getId()) {
            return $this->response->redirect(['/user/session/login?redirect=' . $this->request->get('redirect', null, $this->request->getUrl())]);
        }

        return null;
    }

    /**
     * @return void
     * @throws \ManaPHP\Configuration\Configure\Exception
     */
    public function main()
    {
        $this->configure->loadFile('@app/config.php');

        $this->registerServices();
        $this->alias->set('@messages', '@app/Messages');
        $this->view->setLayout();

        $this->router->setAreas(['Menu', 'Rbac', 'User']);

        if ($this->configure->debug) {
            $this->response->setHeader('X-DEBUGGER', $this->debugger->getUrl());
            $this->handle();
        } else {
            try {
                $this->handle();
            } catch (\Exception $e) {
                $this->errorHandler->handleException($e);
            }
        }

        $this->response->send();
    }
}
