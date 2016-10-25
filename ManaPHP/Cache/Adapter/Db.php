<?php
namespace ManaPHP\Cache\Adapter;

use ManaPHP\Cache\AdapterInterface;
use ManaPHP\Component;

/**
 * Class ManaPHP\Cache\Adapter\Db
 *
 * @package cache\adapter
 */
class Db extends Component implements AdapterInterface
{
    /**
     * @var string
     */
    protected $_model = 'ManaPHP\Cache\Adapter\Db\Model';

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
         * @var \ManaPHP\Cache\Adapter\Db\Model $model
         */
        $model = new $this->_model;
        $model = $model::findFirst(['hash' => md5($key)]);

        return $model !== false && $model->expired_time >= time();
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
         * @var \ManaPHP\Cache\Adapter\Db\Model $model
         */
        $model = new $this->_model;
        $model = $model::findFirst(['hash' => md5($key)]);

        if ($model !== false && $model->expired_time > time()) {
            return $model->value;
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
     * @throws \ManaPHP\Cache\Adapter\Exception
     */
    public function set($key, $value, $ttl)
    {
        /**
         * @var \ManaPHP\Cache\Adapter\Db\Model $model
         */
        $model = new $this->_model;

        $model->hash = md5($key);
        $model->key = $key;
        $model->value = $value;
        $model->expired_time = time() + $ttl;

        $model->save();
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
         * @var \ManaPHP\Cache\Adapter\Db\Model $model
         */
        $model = new $this->_model;

        $model::deleteAll(['hash' => md5($key)]);
    }
}