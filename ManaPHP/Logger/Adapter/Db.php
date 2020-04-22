<?php

namespace ManaPHP\Logger\Adapter;

use Exception;
use ManaPHP\Logger;

/**
 * Class ManaPHP\Logger\Adapter\Db
 *
 * @package logger
 */
class Db extends Logger
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
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        if (isset($options['db'])) {
            $this->_db = $options['db'];
        }

        if (isset($options['table'])) {
            $this->_table = $options['table'];
        }
    }

    /**
     * @param \ManaPHP\Logger\Log[] $logs
     *
     * @return void
     */
    public function append($logs)
    {
        $context = $this->_context;

        /** @var \ManaPHP\DbInterface $db */
        $db = $this->_di->getShared($this->_db);

        $level = $context->level;
        $context->level = Logger::LEVEL_FATAL;

        foreach ($logs as $log) {
            try {
                $db->insert($this->_table, [
                    'host' => $log->host,
                    'client_ip' => $log->client_ip,
                    'request_id' => $log->request_id,
                    'category' => $log->category,
                    'level' => $log->level,
                    'file' => $log->file,
                    'line' => $log->line,
                    'message' => $log->message,
                    'timestamp' => $log->timestamp - (int)$log->timestamp,
                    'created_time' => (int)$log->timestamp
                ]);
            } catch (Exception $e) {
                null;
            }
        }
        $context->level = $level;
    }
}