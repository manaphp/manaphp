<?php

namespace ManaPHP\Caching\Cache\Adapter;

use ManaPHP\Caching\AbstractCache;

/**
 * CREATE TABLE `manaphp_cache` (
 * `hash` char(32) CHARACTER SET ascii NOT NULL,
 * `key` varchar(255) NOT NULL,
 * `value` text NOT NULL,
 * `ttl` int(11) NOT NULL,
 * `expired_time` int(11) NOT NULL,
 * PRIMARY KEY (`hash`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8
 *
 * @property-read \ManaPHP\Data\DbInterface $db
 */
class Db extends AbstractCache
{
    /**
     * @var string
     */
    protected $table = 'manaphp_cache';

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
     * @param string $key
     *
     * @return bool
     */
    public function do_exists($key)
    {
        return $this->db->query($this->table)->whereEq('hash', md5($key))->value('expired_time') >= time();
    }

    /**
     * @param string $key
     *
     * @return string|false
     */
    public function do_get($key)
    {
        $r = $this->db->query($this->table)->whereEq('hash', md5($key))->first();
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
        $hash = md5($key);
        $expired_time = time() + $ttl;

        if ($this->db->query($this->table)->whereEq('hash', $hash)->exists()) {
            $this->db->update($this->table, compact('value', 'ttl', 'expired_time'), ['hash' => $hash]);
        } else {
            $this->db->insert($this->table, compact('hash', 'key', 'value', 'ttl', 'expired_time'));
        }
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function do_delete($key)
    {
        $this->db->delete($this->table, ['hash' => md5($key)]);
    }
}