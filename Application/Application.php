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
     * @throws \ManaPHP\Configure\Exception
     * @throws \ManaPHP\Alias\Exception
     * @throws \ManaPHP\Mvc\Application\Exception
     * @throws \ManaPHP\Mvc\NotFoundException
     * @throws \ManaPHP\Di\Exception
     * @throws \ManaPHP\Db\Exception
     * @throws \ManaPHP\Mvc\Application\NotFoundModuleException
     * @throws \ManaPHP\Mvc\View\Exception
     * @throws \ManaPHP\Renderer\Exception
     * @throws \ManaPHP\Mvc\Dispatcher\Exception
     * @throws \ManaPHP\Mvc\Router\Exception
     * @throws \ManaPHP\Event\Exception
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
            } catch (\ManaPHP\Security\Captcha\Exception $e) {
                if ($this->request->isAjax()) {
                    $this->response->setJsonContent(['code' => __LINE__, 'error' => 'captcha is wrong.']);
                }
            } catch (\Exception $e) {
                $this->errorHandler->handleException($e);
            }
        }

        $this->response->send();
    }
}
