<?php
declare(strict_types=1);

namespace ManaPHP\Messaging\Queue\Adapter;

use ManaPHP\Messaging\AbstractQueue;

/**
 *CREATE TABLE `manaphp_message_queue` (
 * `id` int(11) NOT NULL AUTO_INCREMENT,
 * `priority` tinyint(4) NOT NULL,
 * `topic` char(16) NOT NULL,
 * `body` varchar(4000) NOT NULL,
 * `created_time` int(11) NOT NULL,
 * `deleted_time` int(11) NOT NULL,
 * PRIMARY KEY (`id`)
 * ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8
 *
 * @property-read \ManaPHP\Data\DbInterface $db
 */
class Db extends AbstractQueue
{
    protected string $table;

    public function __construct(string $table = 'manaphp_message_queue')
    {
        $this->table = $table;
    }

    public function do_push(string $topic, string $body, int $priority = self::PRIORITY_NORMAL): void
    {
        $created_time = time();
        $deleted_time = 0;
        $this->db->insert($this->table, compact('topic', 'body', 'priority', 'created_time', 'deleted_time'));
    }

    public function do_pop(string $topic, int $timeout = PHP_INT_MAX): false|string
    {
        $startTime = time();

        $prev_max = null;
        do {
            $max_id = $this->db->query($this->table)->max('id');
            if ($prev_max !== $max_id) {
                $prev_max = $max_id;

                $r = $this->db->query($this->table)
                    ->where(['topic' => $topic, 'deleted_time' => 0])
                    ->orderBy(['priority' => SORT_ASC, 'id' => SORT_ASC])
                    ->first();

                if ($r && $this->db->update($this->table, ['deleted_time' => time()], ['id' => $r['id']])) {
                    return $r['body'];
                }
            }
            sleep(1);
        } while (time() - $startTime < $timeout);

        return false;
    }

    public function do_delete(string $topic): void
    {
        $this->db->delete($this->table, ['topic' => $topic]);
    }

    public function do_length(string $topic, ?int $priority = null): int
    {
        $query = $this->db->query($this->table)->where(['topic' => $topic, 'deleted_time' => 0]);

        if ($priority !== null) {
            $query->where(['priority' => $priority]);
        }

        return $query->count();
    }
}