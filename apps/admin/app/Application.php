<?php
namespace App\Admin;

class Application extends \ManaPHP\Mvc\Application
{
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

        if ($this->identity->isGuest()) {
            return $this->response->redirect(['/user/session/login?redirect=' . $this->request->get('redirect', null, $this->request->getUrl())]);
        }

        return null;
    }

    /**
     * @return void
     */
    public function main()
    {
        $this->loader->registerFiles('@manaphp/helpers.php');
        $this->dotenv->load();
        $this->configure->load();

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
