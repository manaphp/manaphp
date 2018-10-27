<?php
namespace App;

class Application extends \ManaPHP\Mvc\Application
{
    /**
     * @return void
     */
    public function main()
    {
        $this->dotenv->load();
        $this->configure->load();

        $this->registerServices();
        $this->logger->debug(str_pad('', 80, '*'));
        $this->view->setLayout();

        try {
            $this->handle();
        } catch (\Exception $e) {
            $this->handleException($e);
        }

        $this->response->send();
    }
}
