<?php
namespace App\Admin;

use ManaPHP\Authentication\UserIdentity;
use ManaPHP\Authorization\Rbac;

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
     */
    public function main()
    {
        if ($this->_configFile) {
            $this->configure->loadFile($this->_configFile);
        }

        $this->registerServices();
        $this->alias->set('@messages', '@app/Messages');
        $this->view->setLayout();

        $this->router->setAreas(['Menu', 'Rbac', 'User']);

        try {
            $this->handle();
        } catch (\Exception $e) {
            $this->errorHandler->handle($e);
        }

        $this->response->send();
    }
}
