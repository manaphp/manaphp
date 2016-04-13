<?php
namespace ManaPHP\Log\Adapter {

    use ManaPHP\Log\AdapterInterface;

    class File implements AdapterInterface
    {

        /**
         * @var string
         */
        protected $_file;

        /**
         * @var array
         */
        protected $_options = [];

        /**
         * @var bool
         */
        protected $_firstLog = true;

        /**
         * \ManaPHP\Log\Adapter\File constructor.
         *
         * @param string $file
         * @param array  $options
         */
        public function __construct($file, $options = [])
        {
            $this->_file = $file;

            if (!isset($options['dateFormat'])) {
                $options['dateFormat'] = 'D, d M y H:i:s O';
            }

            if (!isset($options['format'])) {
                $options['format'] = '[%date%][%level%] %message%';
            }

            $this->_options = $options;

        }

        /**
         * @param string $level
         * @param string $message
         * @param array  $context
         */
        public function log($level, $message, $context = [])
        {
            if ($this->_firstLog) {
                $dir = dirname($this->_file);

                if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
                    error_log('Unable to create \'' . $dir . '\' directory: ' . error_get_last()['message']);
                }

                $this->_firstLog = false;
            }

            $context['date'] = date($this->_options['dateFormat'], isset($context['date']) ?: time());

            $replaced = [];
            foreach ($context as $k => $v) {
                $replaced["%$k%"] = $v;
            }

            $replaced['%message%'] = $message . PHP_EOL;

            $log = strtr($this->_options['format'], $replaced);

            if (file_put_contents($this->_file, $log, FILE_APPEND | LOCK_EX) === false) {
                error_log('Write log to file failed: ' . $this->_file);
            }
        }
    }
}