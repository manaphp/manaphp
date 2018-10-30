<?php
namespace ManaPHP\Mvc\Dispatcher;

class Listener extends \ManaPHP\Event\Listener
{
    /**
     * @param \ManaPHP\Mvc\DispatcherInterface $dispatcher
     *
     * @return void|false
     */
    public function onBeforeDispatch($dispatcher)
    {

    }

    /**
     * @param \ManaPHP\Mvc\DispatcherInterface $dispatcher
     *
     * @return void
     */
    public function onAfterDispatch($dispatcher)
    {

    }
}