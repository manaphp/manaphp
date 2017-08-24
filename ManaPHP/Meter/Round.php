<?php
namespace ManaPHP\Meter;

use ManaPHP\Component;

/**
 * Class ManaPHP\Meter\Round
 *
 * @package roundMeter
 *
 * @property \Redis $redis
 */
class Round extends Component implements RoundInterface
{
    /**
     * @var string
     */
    protected $_model = 'ManaPHP\Meter\Round\Model';

    /**
     * @var bool
     */
    protected $_useRedis = false;

    /**
     * @var string
     */
    protected $_prefix = 'meter:round:';

    /**
     * Round constructor.
     *
     * @param bool|string|array $options
     */
    public function __construct($options = [])
    {
        if (is_bool($options)) {
            $options = ['useRedis' => $options];
        } elseif (is_string($options)) {
            $options = ['model' => $options];
        }

        if (isset($options['useRedis'])) {
            $this->_useRedis = $options['useRedis'];
        }

        if (isset($options['model'])) {
            $this->_model = $options['model'];
        }

        if (isset($options['prefix'])) {
            $this->_prefix = $options['prefix'];
        }
    }

    /**
     * @param string $type
     * @param string $id
     * @param int    $duration
     *
     * @return static
     * @throws \ManaPHP\Db\Model\Exception
     */
    public function record($type, $id, $duration)
    {
        $begin_time = (int)(time() / $duration) * $duration;
        if ($this->_useRedis) {
            $this->redis->hIncrBy($this->_prefix . $type . ':' . $begin_time . '-' . $duration, $id, 1);
        } else {
            /**
             * @var \ManaPHP\Meter\Round\Model $model
             * @var \ManaPHP\Meter\Round\Model $instance
             */
            $model = new $this->_model();
            $hash = md5($type . ':' . $begin_time . '-' . $duration . ':' . $id);
            $r = $model::updateAll(['count = count + 1'], ['hash' => $hash]);
            if ($r === 0) {
                $instance = new $this->_model();

                $instance->hash = $hash;
                $instance->type = $type;
                $instance->id = $id;
                $instance->count = 1;
                $instance->begin_time = $begin_time;
                $instance->duration = $duration;
                $instance->created_time = time();
                try {
                    $instance->create();
                } catch (\Exception $e) {
                    $model::updateAll(['count = count + 1'], ['hash' => $hash]);
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
     * @throws \ManaPHP\Db\Model\Exception
     */
    public function flush($type, $id = null)
    {
        if ($this->_useRedis) {
            $it = null;

            while ($keys = $this->redis->scan($it, $this->_prefix . $type . ':*', 32)) {
                foreach ($keys as $key) {
                    $parts = explode('-', substr($key, strrpos($key, ':') + 1));
                    $begin_time = $parts[0];
                    $duration = $parts[1];
                    if ($id !== null) {
                        $count = $this->redis->hGet($key, $id);
                        if ($count !== '') {
                            $this->_save($type, $id, $begin_time, $duration, $count);
                        }

                        if (time() - $begin_time > $duration) {
                            $this->redis->hDel($key, $id);
                        }
                    } else {
                        $it2 = null;

                        while ($hashes = $this->redis->hScan($key, $it2, '', 32)) {
                            foreach ($hashes as $hash => $count) {
                                $this->_save($type, $hash, $begin_time, $duration, $count);
                            }
                        }

                        if (time() - $begin_time > $duration) {
                            $this->redis->delete($key);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param string $type
     * @param string $id
     * @param int    $begin_time
     * @param int    $duration
     * @param int    $count
     *
     * @return void
     * @throws \ManaPHP\Db\Model\Exception
     */
    protected function _save($type, $id, $begin_time, $duration, $count)
    {
        /**
         * @var \ManaPHP\Meter\Round\Model $model
         * @var \ManaPHP\Meter\Round\Model $instance
         */
        $model = new $this->_model();
        $hash = md5($type . ':' . $begin_time . '-' . $duration . ':' . $id);
        $r = $model::updateAll(['count' => $count], ['hash' => $hash]);
        if ($r === 0) {
            $instance = new $this->_model();

            $instance->hash = $hash;
            $instance->type = $type;
            $instance->id = $id;
            $instance->count = $count;
            $instance->begin_time = $begin_time;
            $instance->duration = $duration;
            $instance->created_time = time();
            try {
                $instance->create();
            } catch (\Exception $e) {
                $model::updateAll(['count' => $count], ['hash' => $hash]);
            }
        }
    }
}