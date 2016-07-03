<?php

namespace Application {

    use ManaPHP\Authorization\Exception;
    use ManaPHP\AuthorizationInterface;
    use ManaPHP\Component;

    class Authorization extends Component implements AuthorizationInterface
    {
        /**
         * @param \ManaPHP\Mvc\DispatcherInterface $dispatcher
         *
         * @return void
         */
        public function authorize($dispatcher)
        {
            //$this->response->redirect(''); return false;
            //$this->dispatcher->forward(''); return false;

            //throw new Exception('access denied.');
        }

        public function isAllowed($permission, $userId = null)
        {
            return true;
        }
    }
}

