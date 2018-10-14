<?php

namespace ManaPHP\Task;

use ManaPHP\Component;
use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Logger\LogCategorizable;
use ManaPHP\Utility\Text;

class Manager extends Component implements ManagerInterface, LogCategorizable
{
    const FIELD_HOST = 'host';
    const FIELD_CLASS = 'class';
    const FIELD_START_TIME = 'start_time';
    const FIELD_EXCEPTION_COUNT = 'exception_count';
    const FIELD_LAST_EXCEPTION = 'last_exception';
    const FIELD_HEARTBEAT_TIME = 'heartbeat_time';
    const FIELD_DELETE_TIME = 'delete_time';

    /**
     * @var string
     */
    protected $_redis = 'redis';

    /**
     * @var int
     */
    protected $_heartbeat_interval = 10;

    /**
     * @var int
     */
    protected $_lastHeartBeat = 0;

    /**
     * Manager constructor.
     *
     * @param string|array $options
     */
    public function __construct($options = [])
    {
        if (is_string($options)) {
            $this->_redis = $options;
        } else {
            if (isset($options['redis'])) {
                $this->_redis = $options['redis'];
            }

            if (isset($options['heartbeat_interval'])) {
                $this->_heartbeat_interval = $options['heartbeat_interval'];
            }
        }
    }

    /**
     * @return string
     */
    public function categorizeLog()
    {
        return 'tasksManager';
    }

    /**
     * @param string $task
     *
     * @return string
     */
    protected function getTaskMetaKey($task)
    {
        return 'task:' . $task . ':' . gethostname();
    }


    /**
     * @return \Redis
     */
    protected function _getRedis()
    {
        if (!$this->_redis) {
            return null;
        } elseif (is_object($this->_redis)) {
            return $this->_redis;
        } elseif (strpos($this->_redis, '/') !== false) {
            return $this->_redis = $this->_di->getInstance('ManaPHP\Redis', [$this->_redis]);
        } else {
            return $this->_redis = $this->_di->getShared($this->_redis);
        }
    }

    /**
     * @param string $task
     */
    public function run($task)
    {
        if (strpos($task, '\\') === false) {
            $task = Text::underscore(basename($task, 'Task'));
            $className = $this->alias->resolveNS('@ns.app\\Tasks\\' . Text::camelize($task) . 'Task');
        } else {
            $className = $task;
            $task = Text::underscore(basename($task, 'Task'));
        }

        if (!class_exists($className)) {
            throw new InvalidArgumentException('task class is not exists: :task => :class', ['task' => $task, 'class' => $className]);
        }

        /**
         * @var \ManaPHP\TaskInterface $instance
         */
        $this->logger->info(['`:name`(:class) starting...', 'name' => $task, 'class' => $className]);

        $exception_times = 0;
        $last_exception = null;
        $metaKey = $this->getTaskMetaKey($task);
        try {
            $redis = $this->_getRedis();

            if ($redis->exists($metaKey)) {
                $oldMetaKey = $metaKey . ':' . date('mdHi');
                $redis->hSet($metaKey, self::FIELD_DELETE_TIME, date('c'));
                $redis->rename($metaKey, $oldMetaKey);
                /** @noinspection SummerTimeUnsafeTimeManipulationInspection */
                $redis->setTimeout($oldMetaKey, 86400 * 7);
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception);
            $this->logger->info('move old task meta data failed');
        }

        $this->logger->info([':class start success.', 'class' => $className]);
        /**
         * @var \ManaPHP\TaskInterface $instance
         */
        while (true) {
            try {
                $instance = new $className();
                if ($redis = $this->_getRedis()) {
                    $redis->hMSet($metaKey, [
                        self::FIELD_HOST => gethostname(),
                        self::FIELD_CLASS => $className,
                        self::FIELD_START_TIME => date('c')]);
                }
                break;
            } catch (\Exception $e) {
                if (!$last_exception || (string)$last_exception !== (string)$e) {
                    $last_exception = $e;
                    $this->logger->error($e);
                }

                /** @noinspection PowerOperatorCanBeUsedInspection */
                $delay = min(60, pow(2, $exception_times));
                $this->logger->info(['after :delay seconds to retry(:times) create :class instance',
                    'delay' => $delay,
                    'class' => $className,
                    'times' => $exception_times++]);
                sleep($delay);
            } catch (\Throwable $t) {
                $this->logger->fatal($t);
                $this->logger->info([':task task has dead', 'task' => $task]);
                while (true) {
                    sleep(3600);
                }
            }
        }
        $this->logger->info(['`:name` start successfully', 'name' => $task]);

        $last_exception = null;
        $exception_times = 0;
        while (true) {
            try {
                $instance->run();
                $last_exception = null;
                $exception_times = 0;
                for ($i = $instance->getInterval(); $i > 0; $i--) {
                    sleep(1);
                    $this->heartbeat($task);
                }
            } catch (\Exception $e) {
                if (!$last_exception || (string)$last_exception !== (string)$e) {
                    $last_exception = $e;
                    $this->logger->error($e);

                    if ($redis = $this->_getRedis()) {
                        $redis->hIncrBy($metaKey, self::FIELD_EXCEPTION_COUNT, 1);
                        $redis->hSet($metaKey, self::FIELD_LAST_EXCEPTION, (string)$e);
                    }
                }
                /** @noinspection PowerOperatorCanBeUsedInspection */
                $delay = min(pow(2, $exception_times), $instance->getMaxDelay());
                $this->logger->info(['after :delay seconds to retry(:times) run', 'delay' => $delay, 'times' => $exception_times++]);

                for ($i = $delay; $i > 0; $i--) {
                    sleep(1);
                    $this->heartbeat($task);
                }
            } catch (\Throwable $t) {
                $this->logger->fatal($t);
                $this->logger->info([':task task has dead', 'task' => $task]);
                while (true) {
                    sleep(3600);
                }
            }
        }

        $this->logger->info(['`:name` stop successfully: :name => :class', 'name' => $task, 'class' => $className]);
    }

    /**
     * @param string $task
     *
     * @void
     */
    public function heartbeat($task)
    {
        $current = time();
        if ($current - $this->_lastHeartBeat >= $this->_heartbeat_interval) {
            $this->_lastHeartBeat = $current;
            if ($redis = $this->_getRedis()) {
                $redis->hSet($this->getTaskMetaKey($task), self::FIELD_HEARTBEAT_TIME, date('c', $current));
            }
        }
    }
}