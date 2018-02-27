<?php
namespace ManaPHP\Counter\Engine;

use ManaPHP\Counter\Engine\Db\Exception as DbException;
use ManaPHP\Counter\EngineInterface;

/**
 * Class ManaPHP\Counter\Engine\Db
 *
 * @package counter\adapter
 *
 * @property \ManaPHP\DbInterface $db
 */
class Db implements EngineInterface
{
    /**
     * @var string
     */
    protected $_model = '\ManaPHP\Counter\Engine\Db\Model';

    /**
     * @var int
     */
    protected $_maxTries = 100;

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
     * @return int
     */
    public function get($key)
    {
        /**
         * @var \ManaPHP\Counter\Engine\Db\Model $counter
         */
        $counter = new $this->_model;
        $counter = $counter::findFirst(['hash' => md5($key)]);

        return $counter === false ? 0 : (int)$counter->value;
    }

    /**
     * @param string $key
     * @param int    $step
     *
     * @return int
     * @throws \ManaPHP\Counter\Engine\Db\Exception
     */
    public function increment($key, $step = 1)
    {
        $hash = md5($key);

        /**
         * @var \ManaPHP\Counter\Engine\Db\Model $counter
         */
        $counter = new $this->_model;

        $counter = $counter::findFirst(['hash' => $hash]);
        if (!$counter) {
            try {
                $counter = new $this->_model;

                $counter->hash = $hash;
                $counter->key = $key;
                $counter->value = $step;
                $counter->updated_time = $counter->created_time = time();

                $counter->create();

                return (int)$step;
            } catch (\Exception $e) {
                //maybe this record has been inserted by other request.
            }
        }

        for ($i = 0; $i < $this->_maxTries; $i++) {
            $counter = $counter::findFirst(['hash' => $hash]);
            if ($counter === false) {
                return 0;
            }

            $old_value = $counter->value;
            $r = $counter::updateAll(['value =value + ' . $step], ['hash' => $hash, 'value' => $old_value, 'updated_time' => time()]);
            if ($r === 1) {
                return $old_value + $step;
            }
        }

        throw new DbException([
            'update `:key` counter failed: has been tried :times times.'/**m0a877d4eed799613c*/,
            'key' => $key,
            'times' => $this->_maxTries
        ]);
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function delete($key)
    {
        /**
         * @var \ManaPHP\Counter\Engine\Db\Model $counter
         */
        $counter = new $this->_model;

        $counter::deleteAll(['hash' => md5($key)]);
    }
}