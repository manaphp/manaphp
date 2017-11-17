<?php
namespace ManaPHP\Counter\Adapter;

use ManaPHP\Counter\Adapter\Db\Exception as DbException;
use ManaPHP\Counter\AdapterInterface;

/**
 * Class ManaPHP\Counter\Adapter\Db
 *
 * @package counter\adapter
 *
 * @property \ManaPHP\DbInterface $db
 */
class Db implements AdapterInterface
{
    /**
     * @var string
     */
    protected $_model = '\ManaPHP\Counter\Adapter\Db\Model';

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
     * @param string $type
     * @param string $id
     *
     * @return int
     * @throws \ManaPHP\Model\Exception
     */
    public function get($type, $id)
    {
        /**
         * @var \ManaPHP\Counter\Adapter\Db\Model $counter
         */
        $counter = new $this->_model;
        $counter = $counter::findFirst(['hash' => md5($type . ':' . $id)]);

        return $counter === false ? 0 : (int)$counter->value;
    }

    /**
     * @param string $type
     * @param string $id
     * @param int    $step
     *
     * @return int
     * @throws \ManaPHP\Counter\Adapter\Db\Exception
     * @throws \ManaPHP\Model\Exception
     */
    public function increment($type, $id, $step = 1)
    {
        $hash = md5($type . ':' . $id);

        /**
         * @var \ManaPHP\Counter\Adapter\Db\Model $counter
         */
        $counter = new $this->_model;

        $counter = $counter::findFirst(['hash' => $hash]);
        if (!$counter) {
            try {
                $counter = new $this->_model;

                $counter->hash = $hash;
                $counter->type = $type;
                $counter->id = $id;
                $counter->value = $step;

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
            $r = $counter::updateAll(['value =value + ' . $step], ['hash' => $hash, 'value' => $old_value]);
            if ($r === 1) {
                return $old_value + $step;
            }
        }

        throw new DbException('update `:type`:`:id` counter failed: has been tried :times times.'/**m0a877d4eed799613c*/,
            ['type' => $type, 'id' => $id, 'times' => $this->_maxTries]);
    }

    /**
     * @param string $type
     * @param string $id
     *
     * @return void
     * @throws \ManaPHP\Model\Exception
     */
    public function delete($type, $id)
    {
        /**
         * @var \ManaPHP\Counter\Adapter\Db\Model $counter
         */
        $counter = new $this->_model;

        $counter::deleteAll(['hash' => md5($type . ':' . $id)]);
    }
}