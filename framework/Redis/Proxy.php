<?php
declare(strict_types=1);

namespace ManaPHP\Redis;

use ManaPHP\Exception\NotSupportedException;

class Proxy extends \ManaPHP\Pooling\Proxy implements RedisInterface
{
    public function getProxy(): RedisInterface
    {
        throw new NotSupportedException('Proxy cannot to be proxy again.');
    }
}