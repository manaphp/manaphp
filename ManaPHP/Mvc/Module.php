<?php
namespace ManaPHP\Mvc;

use ManaPHP\Component;

/**
 * Class ManaPHP\Mvc\Module
 *
 * @package module
 */
class Module extends Component implements ModuleInterface
{
    /**
     * @var string
     */
    protected $_moduleName;

    /**
     * Module constructor.
     */
    public function __construct($moduleName = null)
    {
        if ($moduleName === null) {
            $parts = explode('\\', get_called_class());
            $moduleName = $parts[1];
        }

        $this->_moduleName = $moduleName;
    }

    public function registerServices($di)
    {

    }

    /**
     * @return void
     */
    public function antiCsrf()
    {
        $ignoreMethods = ['GET', 'HEAD', 'OPTIONS'];
        if (isset($this->csrfToken) && !in_array($this->request->getMethod(), $ignoreMethods, true)
        ) {
            $this->csrfToken->verify();
        }
    }

    public function authenticate()
    {

    }

    public function authorize($controller, $action)
    {
        //return $this->response->redirect('http://www.baidu.com/');

        //$this->dispatcher->forward('index/about');

        //$this->authorization->isAllowed($controller . '::' . $action);

        return true;
    }
}
