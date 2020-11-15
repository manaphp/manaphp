<?php

namespace ManaPHP\Caching\Cache\Adapter;

use ManaPHP\Caching\Cache;

/**
 * Class ManaPHP\Caching\Cache\Adapter\Db
 *
 * CREATE TABLE `manaphp_cache` (
 * `hash` char(32) CHARACTER SET ascii NOT NULL,
 * `key` varchar(255) NOT NULL,
 * `value` text NOT NULL,
 * `ttl` int(11) NOT NULL,
 * `expired_time` int(11) NOT NULL,
 * PRIMARY KEY (`hash`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8
 *
 * @package cache\adapter
 */
class Db extends Cache
{
    /**
     * @var string
     */
    protected $_db = 'db';

    /**
     * @var string
     */
    protected $_source = 'manaphp_cache';

    /**
     * Db constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['db'])) {
            $this->_db = $options['db'];
        }

        if (isset($options['source'])) {
            $this->_source = $options['source'];
        }
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function do_exists($key)
    {
        /** @var \ManaPHP\DbInterface $db */
        $db = $this->getShared($this->_db);

        return $db->query($this->_source)->whereEq('hash', md5($key))->value('expired_time') >= time();
    }

    /**
     * @param string $key
     *
     * @return string|false
     */
    public function do_get($key)
    {
        /** @var \ManaPHP\DbInterface $db */
        $db = $this->getShared($this->_db);

        $r = $db->query($this->_source)->whereEq('hash', md5($key))->first();
        if ($r && $r['expired_time'] > time()) {
            return $r['value'];
        } else {
            return false;
        }
    }

    /**
     * @param string $key
     * @param string $value
     * @param int    $ttl
     *
     * @return void
     */
    public function do_set($key, $value, $ttl)
    {
        /** @var \ManaPHP\DbInterface $db */
        $db = $this->getShared($this->_db);

        $hash = md5($key);
        $expired_time = time() + $ttl;

        if ($db->query($this->_source)->whereEq('hash', $hash)->exists()) {
            $db->update($this->_source, compact('value', 'ttl', 'expired_time'), ['hash' => $hash]);
        } else {
            $db->insert($this->_source, compact('hash', 'key', 'value', 'ttl', 'expired_time'));
        }
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function do_delete($key)
    {
        /** @var \ManaPHP\DbInterface $db */
        $db = $this->getShared($this->_db);
        $db->delete($this->_source, ['hash' => md5($key)]);
    }
}