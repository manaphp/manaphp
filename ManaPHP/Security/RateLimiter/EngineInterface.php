<?php
namespace ManaPHP\Security\RateLimiter;

interface EngineInterface
{
    /**
     * @param string $type
     * @param string $id
     * @param int    $duration
     *
     * @return int
     */
    public function check($type, $id, $duration);
}