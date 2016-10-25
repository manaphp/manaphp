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
     * @param string|array $controllerAction
     * @param int          $duration
     * @param int          $ip_times
     * @param int          $user_times
     *
     * @return void
     */
    public function limit($controllerAction, $duration, $ip_times, $user_times = null);

    /**
     * @param string $resource
     * @param int    $duration
     * @param int    $ip_times
     * @param int    $user_times
     *
     * @return void
     */
    public function limitAny($resource, $duration, $ip_times, $user_times = null);
}