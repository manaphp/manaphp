<?php
namespace ManaPHP\Store\Adapter;

use ManaPHP\Component;
use ManaPHP\Store\AdapterInterface;

/**
 * Class ManaPHP\Store\Adapter\Db
 *
 * @package store\adapter
 */
class Db extends Component implements AdapterInterface
{
    /**
     * @var string
     */
    protected $_model = 'ManaPHP\Store\Adapter\Db\Model';

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
         * @var \ManaPHP\Store\Adapter\Db\Model $model
         */
        $model = new $this->_model;

        return $model::exists(['hash' => md5($key)]);
    }

    /**
     * @param string $key
     *
     * @return string|false
     * @throws \ManaPHP\Db\Query\Exception
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public function get($key)
    {
        /**
         * @var \ManaPHP\Store\Adapter\Db\Model $model
         */
        $model = new $this->_model;
        $model = $model::findFirst(['hash' => md5($key)]);

        return $model === false ? false : $model->value;
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return void
     * @throws \ManaPHP\Mvc\Model\Exception
     * @throws \ManaPHP\Store\Adapter\Exception
     */
    public function set($key, $value)
    {
        /**
         * @var \ManaPHP\Store\Adapter\Db\Model $model
         */
        $model = new $this->_model;

        $model->hash = md5($key);
        $model->key = $key;
        $model->value = $value;

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
         * @var \ManaPHP\Store\Adapter\Db\Model $model
         */
        $model = new $this->_model;

        $model::deleteAll(['hash' => md5($key)]);
    }
}