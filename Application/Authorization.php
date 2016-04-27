<?php

namespace Application {

    use ManaPHP\Security\AuthorizationInterface;
    use ManaPHP\Component;

    class Authorization extends Component implements AuthorizationInterface
    {
        /**
         * @param \ManaPHP\Mvc\DispatcherInterface $dispatcher
         *
         * @return bool
         */
        public function authorize($dispatcher)
        {
            //$this->response->redirect(''); return false;
            //$this->dispatcher->forward(''); return false;

            return true;
        }
    }
}

