<?php

namespace App\Home;

class Application extends \ManaPHP\Mvc\Application
{
    /**
     * @return void
     * @throws \ManaPHP\Configuration\Configure\Exception
     */
    public function main()
    {
        $this->configure->loadFile('@app/config.php');

        $this->registerServices();

        try {
            $this->handle();
        } catch (\Exception $e) {
            $this->errorHandler->handle($e);
        }

        $this->response->setHeader('X-Response-Time', round(microtime(true)-$_SERVER['REQUEST_TIME_FLOAT'],3));
        $this->response->send();
    }
}
