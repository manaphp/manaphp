<?php
namespace App\Api;

class Application extends \ManaPHP\Mvc\Application
{
    /**
     * @return void
     * @throws \ManaPHP\Configuration\Configure\Exception
     */
    public function main()
    {
        $this->configure->loadFile($this->configFile, $this->env);

        $this->registerServices();

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
