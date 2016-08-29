<?php
namespace ManaPHP\Cache\Engine;

use ManaPHP\Cache\EngineInterface;
use ManaPHP\Component;

class Db extends Component implements EngineInterface
{
    /**
     * @var string
     */
    protected $_model = 'ManaPHP\Cache\Engine\Db\Model';

    /**
     * Db constructor.
     *
     * @param string|array $options
     */
    public function __construct($options = [])
    {
        if (is_object($options)) {
            $options = (array)$options;
        } elseif (is_string($options)) {
            $options = ['model' => $options];
        }

        if (isset($options['model'])) {
            $this->_model = $options['model'];
        }
    }

    /**
     * @param string $key
     *
     * @return bool
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public function exists($key)
    {
        /**
         * @var \ManaPHP\Cache\Engine\Db\Model $cache
         */
        $cache = new $this->_model;
        $cache = $cache::findFirst(['hash' => md5($key)]);

        return $cache !== false && $cache->expired_time >= time();
    }

    /**
     * @param string $key
     *
     * @return string|false
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public function get($key)
    {
        /**
         * @var \ManaPHP\Cache\Engine\Db\Model $cache
         */
        $cache = new $this->_model;
        $cache = $cache::findFirst(['hash' => md5($key)]);

        if ($cache !== false && $cache->expired_time > time()) {
            return $cache->value;
        } else {
            return false;
        }
    }

    /**
     * @param string $key
     * @param string $value
     * @param int    $ttl
     *
     * @return void
     * @throws \ManaPHP\Mvc\Model\Exception
     * @throws \ManaPHP\Cache\Engine\Exception
     */
    public function set($key, $value, $ttl)
    {
        /**
         * @var \ManaPHP\Cache\Engine\Db\Model $cache
         */
        $cache = new $this->_model;

        $cache->hash = md5($key);
        $cache->key = $key;
        $cache->value = $value;
        $cache->expired_time = time() + $ttl;

        $cache->save();
    }

    /**
     * @param string $key
     *
     * @return void
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public function delete($key)
    {
        /**
         * @var \ManaPHP\Cache\Engine\Db\Model $cache
         */
        $cache = new $this->_model;

        $cache::deleteAll(['hash' => md5($key)]);
    }
}