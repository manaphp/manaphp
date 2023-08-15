<?php
declare(strict_types=1);

namespace ManaPHP\Http\Session\Adapter;

use ManaPHP\Data\DbInterface;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Di\Attribute\Value;
use ManaPHP\Http\AbstractSession;
use ManaPHP\Http\RequestInterface;
use ManaPHP\Identifying\IdentityInterface;

/**
 * CREATE TABLE `manaphp_session` (
 * `session_id` char(32) CHARACTER SET ascii NOT NULL,
 * `user_id` int(11) NOT NULL,
 * `client_ip` char(15) NOT NULL,
 * `data` text NOT NULL,
 * `updated_time` int(11) NOT NULL,
 * `expired_time` int(11) NOT NULL,
 * PRIMARY KEY (`session_id`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8
 */
class Db extends AbstractSession
{
    #[Inject] protected RequestInterface $request;
    #[Inject] protected DbInterface $db;
    #[Inject] protected IdentityInterface $identity;

    #[Value] protected string $table = 'manaphp_session';

    public function do_read(string $session_id): string
    {
        return $this->db->query($this->table)->where(['session_id' => $session_id])->value('data', '');
    }

    public function do_write(string $session_id, string $data, int $ttl): bool
    {
        $field_values = [
            'user_id'      => $this->identity->getId(0),
            'client_ip'    => $this->request->getClientIp(),
            'data'         => $data,
            'updated_time' => time(),
            'expired_time' => $ttl + time()
        ];

        if ($this->db->query($this->table)->exists()) {
            $this->db->update($this->table, $field_values, ['session_id' => $session_id]);
        } else {
            $field_values['session_id'] = $session_id;
            $this->db->insert($this->table, $field_values);
        }

        return true;
    }

    public function do_touch(string $session_id, int $ttl): bool
    {
        $field_values = [
            'user_id'      => $this->identity->getId(0),
            'client_ip'    => $this->request->getClientIp(),
            'updated_time' => time(),
            'expired_time' => $ttl + time()
        ];

        return $this->db->update($this->table, $field_values, ['session_id' => $session_id]) > 0;
    }

    public function do_destroy(string $session_id): void
    {
        $this->db->delete($this->table, ['session_id' => $session_id]);
    }

    public function do_gc(int $ttl): void
    {
        $this->db->query($this->table)->whereCmp('expired_time', '<=', time())->delete();
    }
}