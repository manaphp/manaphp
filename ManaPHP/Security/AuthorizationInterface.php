<?php
namespace ManaPHP\Security {

    interface AuthorizationInterface
    {

        /**
         * @param \ManaPHP\Mvc\DispatcherInterface $dispatcher
         *
         * @return void
         */
        public function authorize($dispatcher);
    }
}