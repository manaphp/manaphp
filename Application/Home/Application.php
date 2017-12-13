<?php
namespace Application\Home;

class Application extends \ManaPHP\Mvc\Application
{
    /**
     * @return void
     * @throws \ManaPHP\Configuration\Configure\Exception
     */
    public function main()
    {
        $this->configure->loadFile('@root/Application/config.php', 'dev');
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
