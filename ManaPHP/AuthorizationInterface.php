<?php
namespace ManaPHP {

    interface AuthorizationInterface
    {

        /**
         * @param \ManaPHP\Mvc\DispatcherInterface $dispatcher
         *
         * @return void
         */
        public function authorize($dispatcher);

        /**
         * Check whether a user is allowed to access a permission
         *
         * @param string     $permission
         * @param string|int $userId
         *
         * @return boolean
         */
        public function isAllowed($permission, $userId = null);
    }
}