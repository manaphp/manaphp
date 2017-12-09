<?php
namespace ManaPHP\Store\Engine;

use ManaPHP\Component;
use ManaPHP\Store\EngineInterface;

/**
 * Class ManaPHP\Store\Engine\Db
 *
 * @package store\engine
 */
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
        if (is_string($options)) {
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
     */
    public function exists($key)
    {
        /**
         * @var \ManaPHP\Store\Engine\Db\Model $model
         */
        $model = new $this->_model;

        return $model::exists(['hash' => md5($key)]);
    }

    /**
     * @param string $key
     *
     * @return string|false
     */
    public function get($key)
    {
        /**
         * @var \ManaPHP\Store\Engine\Db\Model $model
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
     * @throws \ManaPHP\Model\Exception
     */
    public function set($key, $value)
    {
        /**
         * @var \ManaPHP\Store\Engine\Db\Model $model
         */
        $model = new $this->_model;

        $model->hash = md5($key);
        $model->key = $key;
        $model->value = $value;
        $model->updated_time = time();

        $model->save();
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function delete($key)
    {
        /**
         * @var \ManaPHP\Store\Engine\Db\Model $model
         */
        $model = new $this->_model;

        $model::deleteAll(['hash' => md5($key)]);
    }
}