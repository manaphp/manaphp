<?php
namespace App;

use ManaPHP\Identity\NoCredentialException;

class Application extends \ManaPHP\Mvc\Application
{
    /**
     *
     * @return true|\ManaPHP\Http\ResponseInterface
     */
    public function authorize()
    {
        try {
            $this->authorization->authorize();
        } catch (NoCredentialException $exception) {
            return $this->response->redirect(['/user/session/login?redirect=' . $this->request->get('redirect', null, $this->request->getUrl())]);
        }
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
