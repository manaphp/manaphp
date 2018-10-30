<?php
namespace ManaPHP\Router;

/**
 * Class Listener
 * @package ManaPHP\Router
 */
class Listener extends \ManaPHP\Event\Listener
{
    /**
     * @param string                   $event
     * @param \ManaPHP\RouterInterface $router
     * @param mixed                    $data
     */
    public function peek($event, $router, $data)
    {

    }

    /**
     * @param \ManaPHP\RouterInterface $router
     *
     * @return void
     */
    public function onBeforeRoute($router)
    {

    }

    /**
     * @param \ManaPHP\RouterInterface $router
     *
     * @return void
     */
    public function onAfterRoute($router)
    {

    }
}