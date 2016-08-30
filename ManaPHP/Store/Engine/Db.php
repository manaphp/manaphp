<?php
namespace ManaPHP\Store\Engine;

use ManaPHP\Component;
use ManaPHP\Store\EngineInterface;

class Db extends Component implements EngineInterface
{
    /**
     * @var string
     */
    protected $_model = 'ManaPHP\Store\Engine\Db\Model';

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
         * @var \ManaPHP\Store\Engine\Db\Model $store
         */
        $store = new $this->_model;

        return $store::exists(['hash' => md5($key)]);
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
         * @var \ManaPHP\Store\Engine\Db\Model $store
         */
        $store = new $this->_model;
        $store = $store::findFirst(['hash' => md5($key)]);

        return $store === false ? false : $store->value;
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return void
     * @throws \ManaPHP\Mvc\Model\Exception
     * @throws \ManaPHP\Store\Engine\Exception
     */
    public function set($key, $value)
    {
        /**
         * @var \ManaPHP\Store\Engine\Db\Model $store
         */
        $store = new $this->_model;

        $store->hash = md5($key);
        $store->key = $key;
        $store->value = $value;

        $store->save();
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
         * @var \ManaPHP\Store\Engine\Db\Model $store
         */
        $store = new $this->_model;

        $store::deleteAll(['hash' => md5($key)]);
    }
}