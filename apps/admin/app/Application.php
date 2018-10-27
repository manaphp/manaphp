<?php
namespace App;

class Application extends \ManaPHP\Mvc\Application
{
    /**
     * @return void
     */
    public function main()
    {
        $this->loader->registerFiles('@manaphp/helpers.php');
        $this->dotenv->load();
        $this->configure->load();

        $this->registerServices();
        $this->logger->debug(str_pad('', 80, '*'));
        $this->alias->set('@messages', '@app/Messages');
        $this->view->setLayout();

        try {
            $this->handle();
        } catch (\Exception $e) {
            $this->errorHandler->handle($e);
        }

        $this->response->send();
    }
}
