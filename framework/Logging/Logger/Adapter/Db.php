<?php
declare(strict_types=1);

namespace ManaPHP\Logging\Logger\Adapter;

use Exception;
use ManaPHP\Logging\AbstractLogger;
use ManaPHP\Logging\Level;
use ManaPHP\Logging\Logger\Log;

/**
 * @property-read \ManaPHP\Data\DbInterface $db
 */
class Db extends AbstractLogger
{
    protected string $table;

    public function __construct(string $table = 'manaphp_log', string $level = Level::DEBUG, ?string $hostname = null)
    {
        parent::__construct($level, $hostname);

        $this->table = $table;
    }

    /** @noinspection PhpUnusedLocalVariableInspection */
    public function append(Log $log): void
    {
        $context = $this->context;

        $level = $context->level;
        $context->level = Level::CRITICAL;

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

        $context->level = $level;
    }
}