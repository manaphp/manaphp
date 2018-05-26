<?php
namespace App\Admin;

use ManaPHP\Security\Identity;
use App\Admin\Areas\Rbac\Components\Rbac;

class Application extends \ManaPHP\Mvc\Application
{
    public function authenticate()
    {
        $this->_di->authorization = new Rbac();
        $this->_di->identity = new Identity($this->session->get('admin_auth', []));
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

        if (in_array($dispatcher->getControllerName(), ['User/Session', 'User/Login'], true)) {
            return true;
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
