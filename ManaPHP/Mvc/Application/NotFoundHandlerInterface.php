<?php
namespace ManaPHP\Mvc\Application {

    interface NotFoundHandlerInterface
    {

        /**
         * @param \ManaPHP\Mvc\Router\NotFoundRouteException $e
         *
         * @return mixed
         */
        public function notFoundRoute($e);

        /**
         * @param \ManaPHP\Mvc\Application\NotFoundModuleException $e
         *
         * @return mixed
         */
        public function notFoundModule($e);

        /**
         * @param \ManaPHP\Mvc\Dispatcher\NotFoundControllerException $e
         *
         * @return mixed
         */
        public function notFoundController($e);

        /**
         * @param \ManaPHP\Mvc\Dispatcher\NotFoundActionException $e
         *
         * @return mixed
         */
        public function notFoundAction($e);
    }
}