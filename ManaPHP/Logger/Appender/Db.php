<?php

namespace ManaPHP\Logger\Appender;

use ManaPHP\Component;
use ManaPHP\Logger\AppenderInterface;

/**
 * Class ManaPHP\Logger\Appender\Db
 *
 * @package logger
 */
class Db extends Component implements AppenderInterface
{
    /**
     * @var string
     */
    protected $_db = 'db';

    /**
     * @var string
     */
    protected $_table = 'manaphp_log';

    /**
     * Db constructor.
     *
     * @param string|array $options
     */
    public function __construct($options = [])
    {
        if (is_string($options)) {
            $this->_table = $options;
        } else {
            if (isset($options['db'])) {
                $this->_db = $options['db'];
            }

            if (isset($options['table'])) {
                $this->_table = $options['table'];
            }
        }
    }

    /**
     * @param \ManaPHP\Logger\Log $log
     *
     * @return void
     */
    public function append($log)
    {
        /**
         * @var \ManaPHP\DbInterface $db
         */
        $db = $this->_di->getShared($this->_db);

        if ($pos = strpos($log->location, ':')) {
            $file = substr($log->location, 0, $pos);
            $line = substr($log->location, $pos + 1);
        } else {
            $file = '';
            $line = '';
        }

        try {
            $db->insert($this->_table, [
                'host' => $log->host,
                'process_id' => $log->process_id,
                'category' => $log->category,
                'level' => $log->level,
                'file' => $file,
                'line' => $line,
                'message' => $log->message,
                'created_time' => $log->timestamp]);
        } catch (\Exception $e) {
            trigger_error('Write log to db failed: ' . $e->getMessage(), E_USER_WARNING);
        }
    }
}