<?php
namespace ManaPHP\Cache;

class Listener extends \ManaPHP\Event\Listener
{
    /**
     * @param \ManaPHP\CacheInterface $cache
     * @param array                   $data
     */
    public function onHit($cache, $data)
    {

    }

    /**
     * @param \ManaPHP\CacheInterface $cache
     * @param array                   $data
     */
    public function onMiss($cache, $data)
    {

    }
}