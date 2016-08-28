<?php
namespace ManaPHP\Log\Adapter;

use ManaPHP\Component;
use ManaPHP\Log\AdapterInterface;

class File extends Component implements AdapterInterface
{
    /**
     * @var string
     */
    protected $_file;

    /**
     * @var string
     */
    protected $_dateFormat = 'D, d M y H:i:s O';

    /**
     * @var string
     */
    protected $_format = '[%date%][%level%] %message%';

    /**
     * @var bool
     */
    protected $_firstLog = true;

    /**
     * \ManaPHP\Log\Adapter\File constructor.
     *
     * @param string|array|\ConfManaPHP\Log\Adapter\File $options
     */
    public function __construct($options = [])
    {
        if (is_object($options)) {
            $options = (array)$options;
        } elseif (is_string($options)) {
            $options = ['file' => $options];
        }

        if (!isset($options['file'])) {
            $options['file'] = '@data/logger/' . date('ymd') . '.log';
        }

        $this->_file = $options['file'];

        if (isset($options['dateFormat'])) {
            $this->_dateFormat = $options['dateFormat'];
        }

        if (isset($options['format'])) {
            $this->_format = $options['format'];
        }
    }

    /**
     * @param string $level
     * @param string $message
     * @param array  $context
     */
    public function log($level, $message, $context = [])
    {
        if ($this->_firstLog) {
            $this->_file = $this->alias->resolve($this->_file);

            $dir = dirname($this->_file);

            /** @noinspection NotOptimalIfConditionsInspection */
            if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
                error_log('Unable to create \'' . $dir . '\' directory: ' . error_get_last()['message']);
            }

            $this->_firstLog = false;
        }

        $context['date'] = date($this->_dateFormat, $context['date'] ?: time());

        $replaced = [];
        foreach ($context as $k => $v) {
            $replaced["%$k%"] = $v;
        }

        $replaced['%message%'] = $message . PHP_EOL;

        $log = strtr($this->_format, $replaced);

        if (file_put_contents($this->_file, $log, FILE_APPEND | LOCK_EX) === false) {
            error_log('Write log to file failed: ' . $this->_file);
        }
    }
}