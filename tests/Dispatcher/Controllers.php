<?php

namespace App\Test\Controllers {

    use ManaPHP\Exception;
    use ManaPHP\Mvc\Controller;

    class Test1Controller extends Controller
    {

    }

    class Test2Controller extends Controller
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

        public function another4Action()
        {
            return 120;
        }

        public function another5Action()
        {
            return $this->dispatcher->getParam('param1') + $this->dispatcher->getParam('param2');
        }

    }

    class Test4Controller extends Controller
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

    class ControllerBase extends Controller
    {
        public function serviceAction()
        {
            return 'hello';
        }

    }

    class Test5Controller extends Controller
    {
        public function notFoundAction()
        {
            return 'not-found';
        }

    }

    class Test6Controller extends Controller
    {

    }

    /** @noinspection LongInheritanceChainInspection */

    class Test7Controller extends ControllerBase
    {

    }

    class Test8Controller extends Controller
    {
        public function buggyAction()
        {
            throw new Exception('This is an uncaught exception');
        }

    }
}
