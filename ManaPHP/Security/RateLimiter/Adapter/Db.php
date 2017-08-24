<?php
namespace ManaPHP\Security\RateLimiter\Adapter;

use ManaPHP\Security\RateLimiter;

/**
 * Class ManaPHP\Security\RateLimiter\Adapter\Db
 *
 * @package rateLimiter\adapter
 */
class Db extends RateLimiter
{
    /**
     * @var string
     */
    protected $_model = 'ManaPHP\Security\RateLimiter\Adapter\Db\Model';

    /**
     * Db constructor.
     *
     * @param string|array $options
     */
    public function __construct($options = [])
    {
        if (is_string($options)) {
            $options = ['model' => $options];
        }

        if (isset($options['model'])) {
            $this->_model = $options['model'];
        }
    }

    /**
     * @param string $id
     * @param string $resource
     * @param int    $duration
     * @param int    $times
     *
     * @return bool
     * @throws \ManaPHP\Db\Query\Exception
     * @throws \ManaPHP\Db\Model\Exception
     */
    protected function _limit($id, $resource, $duration, $times)
    {
        /**
         * @var \ManaPHP\Security\RateLimiter\Adapter\Db\Model $model
         * @var \ManaPHP\Security\RateLimiter\Adapter\Db\Model $rateLimiter
         */
        $model = new $this->_model();

        $rateLimiter = $model::findFirst(['hash' => md5($id . $resource)]);
        if (!$rateLimiter) {
            $rateLimiter = new $this->_model();

            $rateLimiter->hash = md5($id . $resource);
            $rateLimiter->id = $id;
            $rateLimiter->resource = $resource;
            $rateLimiter->expired_time = time() + $duration;
            $rateLimiter->times = 1;
        } else {
            if (time() > $rateLimiter->expired_time) {
                $rateLimiter->expired_time = time() + $duration;
                $rateLimiter->times = 1;
            } else {
                $rateLimiter->times++;
            }
        }

        $rateLimiter->save();
        return $rateLimiter->times <= $times;
    }
}