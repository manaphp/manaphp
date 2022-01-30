<?php
declare(strict_types=1);

namespace ManaPHP\Logging\Logger\Adapter;

use Exception;
use ManaPHP\Logging\AbstractLogger;

/**
 * @property-read \ManaPHP\Data\DbInterface $db
 */
class Db extends AbstractLogger
{
    protected string $table = 'manaphp_log';

    public function __construct(array $options = [])
    {
        parent::__construct($options);

        if (isset($options['table'])) {
            $this->table = $options['table'];
        }
    }

    /** @noinspection PhpUnusedLocalVariableInspection */
    public function append(array $logs): void
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