<?php
namespace ManaPHP\Security\RateLimiter\Adapter\Db;

/**
 * Class ManaPHP\Security\RateLimiter\Adapter\Db\Model
 *
 * @package rateLimiter\adapter
 */
class Model extends \ManaPHP\Db\Model
{
    /**
     * @var string
     */
    public $hash;
    /**
     * @var string
     */
    public $id;

    /**
     * @var int
     */
    public $resource;

    /**
     * @var int
     */
    public $times;

    /**
     * @var int
     */
    public $expired_time;

    public static function getSource($context = null)
    {
        return 'manaphp_rate_limiter';
    }
}