<?php
namespace ManaPHP\Http\Session\Engine;

use ManaPHP\Component;
use ManaPHP\Http\Session\EngineInterface;

/**
 * Class ManaPHP\Http\Session\Engine\Redis
 *
 * @package session\engine
 *
 * @property \Redis $sessionRedis
 */
class Redis extends Component implements EngineInterface
{
    /**
     * @var string|\ManaPHP\Redis
     */
    protected $_redis = 'redis';

    /**
     * @var string
     */
    protected $_prefix = 'session:';

    /**
     * Redis constructor.
     *
     * @param string|array $options
     */
    public function __construct($options = 'redis')
    {
        if (is_string($options)) {
            $this->_redis = $options;
        } elseif (is_object($options)) {
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
     * @return \ManaPHP\Redis
     */
    protected function _getRedis()
    {
        if (strpos($this->_redis, '/') !== false) {
            return $this->_redis = $this->_dependencyInjector->getInstance('ManaPHP\Redis', [$this->_redis]);
        } else {
            return $this->_redis = $this->_dependencyInjector->getShared($this->_redis);
        }
    }

    /**
     * @param string $savePath
     * @param string $sessionName
     *
     * @return bool
     */
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     * @return bool
     */
    public function close()
    {
        return true;
    }

    /**
     * @param string $sessionId
     *
     * @return string
     */
    public function read($sessionId)
    {
        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
        $data = $redis->get($this->_prefix . $sessionId);
        return is_string($data) ? $data : '';
    }

    /**
     * @param string $sessionId
     * @param string $data
     * @param int    $ttl
     *
     * @return bool
     */
    public function write($sessionId, $data, $ttl)
    {
        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
        return $redis->set($this->_prefix . $sessionId, $data, $ttl);
    }

    /**
     * @param string $sessionId
     *
     * @return bool
     */
    public function destroy($sessionId)
    {
        $redis = is_object($this->_redis) ? $this->_redis : $this->_getRedis();
        $redis->delete($this->_prefix . $sessionId);

        return true;
    }

    /**
     * @param int $ttl
     *
     * @return bool
     */
    public function gc($ttl)
    {
        $this->clean();

        return true;
    }

    /**
     * @return void
     */
    public function clean()
    {

    }
}