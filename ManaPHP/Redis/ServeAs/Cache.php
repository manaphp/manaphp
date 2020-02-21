<?php
namespace ManaPHP\Redis\ServeAs;

use ManaPHP\Redis;

class Cache extends Redis
{
    public function __construct($url = null)
    {
        $this->_serve_as = self::SERVE_AS_CACHE;

        parent::__construct($url);
    }
}
