<?php
namespace ManaPHP\Security\RateLimiter\Engine\Db;

/**
 * Class ManaPHP\Security\RateLimiter\Engine\Db\Model
 *
 * @package rateLimiter\engine
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
    public $type;

    /**
     * @var string
     */
    public $id;

    /**
     * @var int
     */
    public $times;

    /**
     * @var int
     */
    public $expired_time;

    public function getSource($context = null)
    {
        return 'manaphp_rate_limiter';
    }
}