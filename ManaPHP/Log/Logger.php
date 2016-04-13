<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2016/3/20
 */
namespace ManaPHP\Log {

    class Logger
    {
        const LEVEL_OFF = 0;

        const LEVEL_FATAL = 10;
        const LEVEL_ERROR = 20;
        const LEVEL_WARNING = 30;
        const LEVEL_INFO = 40;
        const LEVEL_DEBUG = 50;

        const LEVEL_ALL = 100;

        /**
         * @var int
         */
        protected $_level = self::LEVEL_ALL;

        /**
         * @var array
         */
        protected $_level_i2s;

        /**
         * @var \ManaPHP\Log\AdapterInterface[]
         */
        protected $_adapters = [];

        public function __construct()
        {
            $this->_level_i2s = [
                self::LEVEL_OFF => 'OFF',
                self::LEVEL_FATAL => 'FATAL',
                self::LEVEL_ERROR => 'ERROR',
                self::LEVEL_WARNING => 'WARNING',
                self::LEVEL_INFO => 'INFO',
                self::LEVEL_DEBUG => 'DEBUG',
                self::LEVEL_ALL => 'ALL',
            ];
        }

        /**
         * Filters the logs sent to the handlers to be greater or equals than a specific level
         *
         * @param int|string $level
         *
         * @return static
         * @throws \ManaPHP\Log\Exception
         */
        public function setLevel($level)
        {
            if (is_int($level)) {
                $this->_level = $level;
            } else {
                $s2i = array_flip($this->_level_i2s);
                if (isset($s2i[$level])) {
                    $this->_level = $s2i[$level];
                } else {
                    throw new Exception('Log Level is invalid: ' . $level);
                }
            }

            return $this;
        }

        /**
         * Returns the current log level
         *
         * @return int
         */
        public function getLevel()
        {
            return $this->_level;
        }

        /**
         * @param int $level
         *
         * @return string
         */
        public function mapLevelToString($level)
        {
            if (isset($this->_level_i2s[$level])) {
                return $this->_level_i2s[$level];
            } else {
                return 'UNKNOWN';
            }
        }

        /**
         * @param \ManaPHP\Log\AdapterInterface $adapter
         *
         * @return static
         */
        public function addAdapter($adapter)
        {
            $this->_adapters[] = $adapter;

            return $this;
        }

        /**
         * @param int    $level
         * @param string $message
         * @param array  $context
         *
         * @return static
         */
        protected function _log($level, $message, $context)
        {

            if ($level > $this->_level) {
                return $this;
            }

            $context['level'] = $this->_level_i2s[$level];
            $context['date'] = time();

            foreach ($this->_adapters as $adapter) {
                try {
                    $adapter->log($level, $message, $context);
                } catch (\Exception $e) {
                    error_log('Log Failed: ' . $e->getMessage(), 0);
                }
            }

            return $this;
        }

        /**
         * Sends/Writes a debug message to the log
         *
         * @param string $message
         * @param array  $context
         *
         * @return static
         */
        public function debug($message, $context = [])
        {
            return $this->_log(self::LEVEL_DEBUG, $message, $context);
        }

        /**
         * Sends/Writes an info message to the log
         *
         * @param string $message
         * @param array  $context
         *
         * @return static
         */
        public function info($message, $context = [])
        {
            return $this->_log(self::LEVEL_INFO, $message, $context);
        }

        /**
         * Sends/Writes a warning message to the log
         *
         * @param string $message
         * @param array  $context
         *
         * @return static
         */
        public function warning($message, $context = [])
        {
            return $this->_log(self::LEVEL_WARNING, $message, $context);
        }

        /**
         * Sends/Writes an error message to the log
         *
         * @param string $message
         * @param array  $context
         *
         * @return static
         */
        public function error($message, $context = [])
        {
            return $this->_log(self::LEVEL_ERROR, $message, $context);
        }

        /**
         * Sends/Writes a critical message to the log
         *
         * @param string $message
         * @param array  $context
         *
         * @return static
         */
        public function fatal($message, $context = [])
        {
            return $this->_log(self::LEVEL_FATAL, $message, $context);
        }
    }
}