<?php

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
    /**
     * @var string
     */
    protected $table = 'manaphp_message_queue';

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['table'])) {
            $this->table = $options['table'];
        }
    }

    /**
     * @param string $topic
     * @param string $body
     * @param int    $priority
     *
     * @return void
     */
    public function do_push($topic, $body, $priority = self::PRIORITY_NORMAL)
    {
        $created_time = time();
        $deleted_time = 0;
        $this->db->insert($this->table, compact('topic', 'body', 'priority', 'created_time', 'deleted_time'));
    }

    /**
     * @param string $topic
     * @param int    $timeout
     *
     * @return string|false
     */
    public function do_pop($topic, $timeout = PHP_INT_MAX)
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

    /**
     * @param string $topic
     *
     * @return void
     */
    public function do_delete($topic)
    {
        $this->db->delete($this->table, ['topic' => $topic]);
    }

    /**
     * @param string $topic
     * @param int    $priority
     *
     * @return int
     */
    public function do_length($topic, $priority = null)
    {
        $query = $this->db->query($this->table)->where(['topic' => $topic, 'deleted_time' => 0]);

        if ($priority !== null) {
            $query->where(['priority' => $priority]);
        }

        return $query->count();
    }
}