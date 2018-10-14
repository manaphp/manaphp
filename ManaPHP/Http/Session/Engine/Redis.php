<?php
namespace ManaPHP\Http\Session\Engine;

use ManaPHP\Component;
use ManaPHP\Http\Session\EngineInterface;

/**
 * Class ManaPHP\Http\Session\Engine\Redis
 *
 * @package session\engine
 */
class Redis extends Component implements EngineInterface
{
    /**
     * @var string|\Redis
     */
    protected $_redis = 'redis';

    /**
     * @var string
     */
    protected $_prefix = 'session:';

    /**
     * Redis constructor.
     *
     * @param string|\Redis|array $options
     */
    public function __construct($options = 'redis')
    {
        if (is_string($options) || is_object($options)) {
            $this->_redis = $options;
        } else {
            if (isset($options['redis'])) {
                $this->_redis = $options['redis'];
            }

            if (isset($options['prefix'])) {
                $this->_prefix = $options['prefix'];
            }
        }
    }

    /**
     * @return \Redis
     */
    protected function _getRedis()
    {
        if (strpos($this->_redis, '/') !== false) {
            return $this->_redis = $this->_di->getInstance('ManaPHP\Redis', [$this->_redis]);
        } else {
            return $this->_redis = $this->_di->getShared($this->_redis);
        }
    }

    /**
     * @param string $session_id
     *
     * @return string
     */
    public function read($session_id)
    {
        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
        $data = $redis->get($this->_prefix . $session_id);
        return is_string($data) ? $data : '';
    }

    /**
     * @param string $session_id
     * @param string $data
     * @param array  $context
     *
     * @return bool
     */
    public function write($session_id, $data, $context)
    {
        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
        return $redis->set($this->_prefix . $session_id, $data, $context['ttl']);
    }

    /**
     * @param string $session_id
     *
     * @return bool
     */
    public function destroy($session_id)
    {
        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
        $redis->delete($this->_prefix . $session_id);

        return true;
    }

    /**
     * @param int $ttl
     *
     * @return bool
     */
    public function gc($ttl)
    {
        return true;
    }
}