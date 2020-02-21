<?php
namespace ManaPHP\Redis\ServeAs;

use ManaPHP\Redis;

class Broker extends Redis
{
    public function __construct($url = null)
    {
        $this->_serve_as = self::SERVE_AS_BROKER;

        parent::__construct($url);
    }
}
