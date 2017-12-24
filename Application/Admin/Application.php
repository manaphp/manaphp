<?php
namespace Application\Admin;

use ManaPHP\Authentication\UserIdentity;
use ManaPHP\Authorization\Rbac;
require ROOT_PATH.'/ManaPHP/Mvc/Url/helpers.php';

class Application extends \ManaPHP\Mvc\Application
{
    public function authenticate()
    {
        $this->_dependencyInjector->authorization = new Rbac();
        $this->_dependencyInjector->userIdentity = new UserIdentity($this->session->get('admin_auth', []));
    }

    /**
     * @param \ManaPHP\Mvc\DispatcherInterface $dispatcher
     */
    public function authorize($dispatcher)
    {
        if (!$this->userIdentity->getId() && !in_array($dispatcher->getControllerName() . ':' . $dispatcher->getActionName(), ['User:captcha', 'User:login', 'User:register'],
                true)) {
                 return $this->response->redirect(['/user/login?redirect=' . $this->request->get('redirect', null, $this->request->getUrl(true))]);
        }
    }

    /**
     * @return void
     * @throws \ManaPHP\Configuration\Configure\Exception
     */
    public function main()
    {
        $this->configure->loadFile('@app/../config.php', 'dev');
        $this->alias->set('@messages', '@app/Messages');
        if ($this->configure->debug) {
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
