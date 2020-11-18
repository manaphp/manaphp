<?php

namespace ManaPHP\Logging\Logger\Adapter;

use Exception;
use ManaPHP\Logging\Logger;

class Db extends Logger
{
    /**
     * @var string
     */
    protected $_table = 'manaphp_log';

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['db'])) {
            $this->_injections['db'] = $options['db'];
        }

        parent::__construct($options);

        if (isset($options['table'])) {
            $this->_table = $options['table'];
        }
    }

    /**
     * @param \ManaPHP\Logging\Logger\Log[] $logs
     *
     * @return void
     */
    public function append($logs)
    {
        $context = $this->_context;

        $level = $context->level;
        $context->level = Logger::LEVEL_FATAL;

        foreach ($logs as $log) {
            try {
                $this->db->insert(
                    $this->_table, [
                        'host'         => $log->host,
                        'client_ip'    => $log->client_ip,
                        'request_id'   => $log->request_id,
                        'category'     => $log->category,
                        'level'        => $log->level,
                        'file'         => $log->file,
                        'line'         => $log->line,
                        'message'      => $log->message,
                        'timestamp'    => $log->timestamp - (int)$log->timestamp,
                        'created_time' => (int)$log->timestamp
                    ]
                );
            } catch (Exception $e) {
                null;
            }
        }
        $context->level = $level;
    }
}