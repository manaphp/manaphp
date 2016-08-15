<?php
namespace App\Test\Controllers {

    class Test1Controller extends \ManaPHP\Mvc\Controller
    {

    }

    class Test2Controller extends \ManaPHP\Mvc\Controller
    {
        public function indexAction()
        {
        }

        public function otherAction()
        {

        }

        public function anotherAction()
        {
            return 100;
        }

        public function another2Action($a, $b)
        {
            return $a + $b;
        }

        public function another3Action()
        {
            return $this->dispatcher->forward('test2/another4');
        }

        public function another4Action()
        {
            return 120;
        }

        public function another5Action()
        {
            return $this->dispatcher->getParam('param1') + $this->dispatcher->getParam('param2');
        }

    }

    class Test4Controller extends \ManaPHp\Mvc\Controller
    {
        public function requestAction()
        {
            return $this->request->getPost('email', 'email');
        }

        public function viewAction()
        {
            return $this->view->setParamToView('born', 'this');
        }
    }

    class ControllerBase extends \ManaPHP\Mvc\Controller
    {
        public function serviceAction()
        {
            return 'hello';
        }

    }

    class Test5Controller extends \ManaPHP\Mvc\Controller
    {
        public function notFoundAction()
        {
            return 'not-found';
        }

    }

    class Test6Controller extends \ManaPHP\Mvc\Controller
    {

    }

    /** @noinspection LongInheritanceChainInspection */
    class Test7Controller extends ControllerBase
    {

    }

    class Test8Controller extends \ManaPHP\Mvc\Controller
    {
        public function buggyAction()
        {
            throw new \ManaPHP\Exception('This is an uncaught exception');
        }

    }
}
