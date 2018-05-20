<?php
namespace ManaPHP\Meter;

use ManaPHP\Component;

/**
 * Class ManaPHP\Meter\Linear
 *
 * @package linearMeter
 *
 * @property \Redis $redis
 */
class Linear extends Component implements LinearInterface
{
    /**
     * @var string
     */
    protected $_model = 'ManaPHP\Meter\Linear\Model';

    /**
     * @var bool
     */
    protected $_useRedis = false;

    /**
     * @var string
     */
    protected $_prefix = 'meter:linear:';

    /**
     * Linear constructor.
     *
     * @param bool|string|array $options
     */
    public function __construct($options = [])
    {
        if (is_bool($options)) {
            $options = ['useRedis' => $options];
        } elseif (is_string($options)) {
            $options = ['model' => $options];
        } else {
            $options = (array)$options;
        }

        if (isset($options['model'])) {
            $this->_model = $options['model'];
        }

        if (isset($options['useRedis'])) {
            $this->_useRedis = $options['useRedis'];
        }

        if (isset($options['prefix'])) {
            $this->_prefix = $options['prefix'];
        }
    }

    /**
     * @param string $type
     * @param string $id
     *
     * @return static
     */
    public function record($type, $id)
    {
        if ($this->_useRedis) {
            $this->redis->hIncrBy($this->_prefix . $type, $id, 1);
        } else {
            /**
             * @var \ManaPHP\Meter\Linear\Model $model
             * @var \ManaPHP\Meter\Linear\Model $instance
             */
            $model = new $this->_model();
            $hash = md5($type . ':' . $id);
            $r = $model::updateAll(['count =count + 1'], ['hash' => $hash]);
            if ($r === 0) {
                $instance = new $this->_model();

                $instance->hash = $hash;
                $instance->type = $type;
                $instance->id = $id;
                $instance->count = 1;
                $instance->created_time = time();

                try {
                    $instance->create();
                } catch (\Exception $e) {
                    $model::updateAll(['count =count + 1'], ['hash' => $hash]);
                }
            }
        }

        return $this;
    }

    /**
     * @param string $type
     * @param string $id
     *
     * @return void
     */
    public function flush($type, $id = null)
    {
        if ($this->_useRedis) {
            if ($id !== null) {
                $count = $this->redis->hGet($this->_prefix . $type, $id);
                if ($count !== '') {
                    $this->_save($type, $id, $count);
                }
            } else {
                $it = null;

                while ($hashes = $this->redis->hScan($this->_prefix . $type, $it, '', 32)) {
                    foreach ($hashes as $hash => $count) {
                        $this->_save($type, $hash, $count);
                    }
                }
            }
        }
    }

    /**
     * @param string $type
     * @param string $id
     * @param int    $count
     *
     * @return void
     */
    protected function _save($type, $id, $count)
    {
        /**
         * @var \ManaPHP\Meter\Linear\Model $model
         * @var \ManaPHP\Meter\Linear\Model $instance
         */
        $hash = md5($type . ':' . $id);
        $model = new $this->_model();
        $r = $model::updateAll(['count' => $count], ['hash' => $hash]);
        if ($r === 0) {
            $instance = new $this->_model();

            $instance->hash = $hash;
            $instance->type = $type;
            $instance->id = $id;
            $instance->count = $count;
            $instance->created_time = time();

            try {
                $instance->create();
            } catch (\Exception $e) {
                $model::updateAll(['count' => $count], ['hash' => $hash]);
            }
        }
    }

    /**
     * @param string $type
     * @param string $id
     *
     * @return int
     */
    public function get($type, $id)
    {
        if ($this->_useRedis) {
            return (int)$this->redis->hGet($this->_prefix . $type, $id);
        } else {
            /**
             * @var \ManaPHP\Meter\Linear\Model $model
             * @var \ManaPHP\Meter\Linear\Model $instance
             */
            $model = new $this->_model();
            $instance = $model::first(['hash' => md5($type . ':' . $id)]);

            return $instance ? (int)$instance->count : 0;
        }
    }
}