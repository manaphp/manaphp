<?php

namespace ManaPHP\Socket;

/**
 * @property-read \ManaPHP\Socket\ServerInterface   $socketServer
 * @property-read \ManaPHP\Socket\ResponseInterface $response
 * @property-read \ManaPHP\Socket\RequestInterface  $request
 */
class Controller extends \ManaPHP\Controller
{
    /**
     * @param string $action
     *
     * @return mixed
     */
    public function invoke($action)
    {
        $method = $action . 'Action';

        return method_exists($this, $method) ? $this->invoker->invoke($this, $method) : null;
    }
}