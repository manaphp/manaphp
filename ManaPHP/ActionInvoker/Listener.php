<?php
namespace ManaPHP\ActionInvoker;

/**
 * Class Listener
 * @package ManaPHP\ActionInvoker
 *
 * @property-read \ManaPHP\Http\RequestInterface $request
 * @property-read \ManaPHP\Http\CookiesInterface $cookies
 */
class Listener extends \ManaPHP\Event\Listener
{
    /**
     * @param \ManaPHP\ActionInvokerInterface $actionInvoker
     * @param string                          $action
     *
     * @return mixed|void
     */
    public function onBeforeInvoke($actionInvoker, $action)
    {

    }

    /**
     * @param \ManaPHP\ActionInvokerInterface $actionInvoker
     * @param array                           $data
     *
     * @return mixed|void
     */
    public function onAfterInvoke($actionInvoker, $data)
    {

    }
}
