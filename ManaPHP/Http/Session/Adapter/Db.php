<?php

namespace ManaPHP\Http\Session\Adapter;

use ManaPHP\Http\AbstractSession;

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
 *
 * @property-read \ManaPHP\Http\RequestInterface         $request
 * @property-read \ManaPHP\Data\DbInterface              $db
 * @property-read \ManaPHP\Identifying\IdentityInterface $identity
 */
class Db extends AbstractSession
{
    /**
     * @var string
     */
    protected $table = 'manaphp_session';

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
     * @param string $session_id
     *
     * @return string
     */
    public function do_read($session_id)
    {
        return $this->db->query($this->table)->whereEq('session_id', $session_id)->value('data', '');
    }

    /**
     * @param string $session_id
     * @param string $data
     * @param int    $ttl
     *
     * @return bool
     */
    public function do_write($session_id, $data, $ttl)
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

    /**
     * @param string $session_id
     * @param int    $ttl
     *
     * @return bool
     */
    public function do_touch($session_id, $ttl)
    {
        $field_values = [
            'user_id'      => $this->identity->getId(0),
            'client_ip'    => $this->request->getClientIp(),
            'updated_time' => time(),
            'expired_time' => $ttl + time()
        ];

        return $this->db->update($this->table, $field_values, ['session_id' => $session_id]) > 0;
    }

    /**
     * @param string $session_id
     *
     * @return bool
     */
    public function do_destroy($session_id)
    {
        $this->db->delete($this->table, ['session_id' => $session_id]);

        return true;
    }

    /**
     * @param int $ttl
     *
     * @return bool
     */
    public function do_gc($ttl)
    {
        $this->db->query($this->table)->whereCmp('expired_time', '<=', time())->delete();

        return true;
    }
}