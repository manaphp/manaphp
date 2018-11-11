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

    /**
     * @param \ManaPHP\Mvc\DispatcherInterface $dispatcher
     * @param string                           $action
     *
     * @return mixed|void
     */
    public function onBeforeInvoke($dispatcher, $action)
    {

    }

    /**
     * @param \ManaPHP\Mvc\DispatcherInterface $dispatcher
     * @param array                            $data
     *
     * @return mixed|void
     */
    public function onAfterInvoke($dispatcher, $data)
    {

    }
}