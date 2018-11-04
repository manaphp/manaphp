<?php
namespace App\Listeners;

use ManaPHP\Router\Listener;

class RouterListener extends Listener
{
    /**
     * @param string                   $event
     * @param \ManaPHP\RouterInterface $router
     * @param mixed                    $data
     */
    public function peek($event, $router, $data)
    {
        $router || $data;

        $this->logger->debug($event);
    }

    /**
     * @param \ManaPHP\RouterInterface $router
     */
    public function onBeforeRoute($router)
    {
        $this->logger->debug($router->getRewriteUri());
    }
}