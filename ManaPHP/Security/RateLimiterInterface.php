<?php
namespace ManaPHP\Security;

/**
 * Interface ManaPHP\Security\RateLimiterInterface
 *
 * @package rateLimiter
 */
interface  RateLimiterInterface
{
    /**
     * @param string $type
     * @param string $id
     * @param int    $times
     * @param int    $duration
     *
     * @return int
     */
    public function limit($type, $id, $times, $duration);

    /**
     * @param int $times
     * @param int $duration
     *
     * @return int
     */
    public function limitIp($times, $duration);

    /**
     * @param int $times
     * @param int $duration
     *
     * @return int
     */
    public function limitUser($times, $duration);
}