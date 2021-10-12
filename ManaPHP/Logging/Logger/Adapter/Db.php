<?php

namespace ManaPHP\Logging\Logger\Adapter;

use Exception;
use ManaPHP\Logging\AbstractLogger;

/**
 * @property-read \ManaPHP\Data\DbInterface $db
 */
class Db extends AbstractLogger
{
    /**
     * @var string
     */
    protected $table = 'manaphp_log';

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct($options);

        if (isset($options['table'])) {
            $this->table = $options['table'];
        }
    }

    /**
     * @param \ManaPHP\Logging\Logger\Log[] $logs
     *
     * @return void
     */
    public function append($logs)
    {
        $context = $this->context;

        $level = $context->level;
        $context->level = AbstractLogger::LEVEL_FATAL;

        foreach ($logs as $log) {
            try {
                $this->db->insert(
                    $this->table, [
                        'hostname'     => $log->hostname,
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