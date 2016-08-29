<?php
namespace ManaPHP\Counter\Adapter;

use ManaPHP\Counter;

/**
 * @property \ManaPHP\DbInterface $db
 */
class Db extends Counter
{
    /**
     * @var string
     */
    protected $_model = '\ManaPHP\Counter\Adapter\Db\Model';

    /**
     * Db constructor.
     *
     * @param string|array|\ConfManaPHP\Counter\Adapter\Db $options
     */
    public function __construct($options = [])
    {
        if (is_object($options)) {
            $options = (array)$options;
        } elseif (is_string($options)) {
            $options['model'] = $options;
        }

        if (isset($options['model'])) {
            $this->_model = $options['model'];
        }

        parent::__construct($options);
    }

    /**
     * @param string $type
     * @param string $id
     *
     * @return int
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public function _get($type, $id)
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
     * @throws \ManaPHP\Counter\Adapter\Exception
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public function _increment($type, $id, $step = 1)
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

        for ($i = 0; $i < 100; $i++) {
            $counter = $counter::findFirst(['hash' => $hash]);
            if ($counter === false) {
                return 0;
            }

            $old_value = $counter->value;
            $r = $counter::updateAll(['value =value + :step'], ['hash' => $hash, 'value' => $old_value], ['step' => $step]);
            if ($r === 1) {
                return $old_value + $step;
            }
        }

        throw new Exception('update counter failed: ' . $type);
    }

    /**
     * @param string $type
     * @param string $id
     *
     * @return void
     * @throws \ManaPHP\Mvc\Model\Exception
     */
    public function _delete($type, $id)
    {
        /**
         * @var \ManaPHP\Counter\Adapter\Db\Model $counter
         */
        $counter = new $this->_model;

        $counter::deleteAll(['hash' => md5($type . ':' . $id)]);
    }
}