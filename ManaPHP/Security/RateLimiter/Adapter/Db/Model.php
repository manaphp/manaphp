<?php
namespace ManaPHP\Security\RateLimiter\Adapter\Db;

/**
 * Class ManaPHP\Security\RateLimiter\Adapter\Db\Model
 *
 * @package ManaPHP\Security\RateLimiter\Adapter\Db
 */
class Model extends \ManaPHP\Mvc\Model
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

    /**
     * @return string
     */
    public function getSource()
    {
        return 'manaphp_rate_limiter';
    }
}