<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2016/1/16
 * Time: 8:38
 */
namespace ManaPHP\Mvc\Dispatcher {

    use ManaPHP\Component;
    use ManaPHP\Event\ListenerInterface;

    class Listener extends Component implements ListenerInterface
    {
        /**
         * @param \ManaPHP\Event\Event    $event
         * @param \ManaPHP\Mvc\Dispatcher $dispatcher
         *
         * @return bool
         */
        public function beforeDispatchLoop($event, $dispatcher)
        {
            true || $event;
            true || $dispatcher;

            return true;
        }

        /**
         * @param \ManaPHP\Event\Event    $event
         * @param \ManaPHP\Mvc\Dispatcher $dispatcher
         *
         * @return bool
         */
        public function beforeDispatch($event, $dispatcher)
        {
            true || $event;
            true || $dispatcher;

            return true;
        }

        /**
         * @param \ManaPHP\Event\Event    $event
         * @param \ManaPHP\Mvc\Dispatcher $dispatcher
         *
         * @return void
         */
        public function afterDispatch($event, $dispatcher)
        {
            true || $event;
            true || $dispatcher;
        }
    }
}