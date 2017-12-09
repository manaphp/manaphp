<?php
namespace Application;

/**
 * @property \ManaPHP\Http\RequestInterface  request
 * @property \ManaPHP\Http\ResponseInterface $response
 * @property \ManaPHP\Mvc\HandlerInterface   $mvcHandler
 * @property \ManaPHP\ErrorHandlerInterface  $errorHandler
 */
class Application extends \ManaPHP\Mvc\Application
{
    /**
     * @return void
     * @throws \ManaPHP\Configuration\Configure\Exception
     */
    public function main()
    {
        $this->configure->loadFile('@app/config.php', 'dev');

        if ($this->configure->debug) {
            $this->registerServices();
            $this->mvcHandler->handle();
        } else {
            try {
                $this->registerServices();
                $this->mvcHandler->handle();
            } catch (\Exception $e) {
                $this->errorHandler->handleException($e);
            }
        }

        $this->response->send();
    }
}
