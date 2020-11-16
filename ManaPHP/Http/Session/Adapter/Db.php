<?php

namespace ManaPHP\Http\Session\Adapter;

use ManaPHP\Http\Session;

/**
 * Class ManaPHP\Http\Session\Adapter\Db
 *
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
 * @package session\adapter
 * @property-read \ManaPHP\Http\RequestInterface $request
 */
class Db extends Session
{
    /**
     * @var string
     */
    protected $_db = 'db';

    /**
     * @var string
     */
    protected $_source = 'manaphp_session';

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

        if (isset($options['source'])) {
            $this->_source = $options['source'];
        }
    }

    /**
     * @param string $session_id
     *
     * @return string
     */
    public function do_read($session_id)
    {
        /** @var \ManaPHP\Data\DbInterface $db */
        $db = $this->getShared($this->_db);

        return $db->query($this->_source)->whereEq('session_id', $session_id)->value('data', '');
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
        /** @var \ManaPHP\Data\DbInterface $db */
        $db = $this->getShared($this->_db);

        $field_values = [
            'user_id'      => $this->identity->getId(0),
            'client_ip'    => $this->request->getClientIp(),
            'data'         => $data,
            'updated_time' => time(),
            'expired_time' => $ttl + time()
        ];

        if ($db->query($this->_source)->exists()) {
            $db->update($this->_source, $field_values, ['session_id' => $session_id]);
        } else {
            $field_values['session_id'] = $session_id;
            $db->insert($this->_source, $field_values);
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
        /** @var \ManaPHP\Data\DbInterface $db */
        $db = $this->getShared($this->_db);

        $field_values = [
            'user_id'      => $this->identity->getId(0),
            'client_ip'    => $this->request->getClientIp(),
            'updated_time' => time(),
            'expired_time' => $ttl + time()
        ];

        return $db->update($this->_source, $field_values, ['session_id' => $session_id]) > 0;
    }

    /**
     * @param string $session_id
     *
     * @return bool
     */
    public function do_destroy($session_id)
    {
        /** @var \ManaPHP\Data\DbInterface $db */
        $db = $this->getShared($this->_db);

        $db->delete($this->_source, ['session_id' => $session_id]);

        return true;
    }

    /**
     * @param int $ttl
     *
     * @return bool
     */
    public function do_gc($ttl)
    {
        /** @var \ManaPHP\Data\DbInterface $db */
        $db = $this->getShared($this->_db);

        $db->query($this->_source)->whereCmp('expired_time', '<=', time())->delete();

        return true;
    }
}