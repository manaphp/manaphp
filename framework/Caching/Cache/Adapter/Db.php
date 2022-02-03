<?php
declare(strict_types=1);

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
    protected string $table = 'manaphp_cache';

    public function __construct(array $options = [])
    {
        if (isset($options['table'])) {
            $this->table = $options['table'];
        }
    }

    public function do_exists(string $key): bool
    {
        return $this->db->query($this->table)->whereEq('hash', md5($key))->value('expired_time') >= time();
    }

    public function do_get(string $key): false|string
    {
        $r = $this->db->query($this->table)->whereEq('hash', md5($key))->first();
        if ($r && $r['expired_time'] > time()) {
            return $r['value'];
        } else {
            return false;
        }
    }

    public function do_set(string $key, string $value, int $ttl): void
    {
        $hash = md5($key);
        $expired_time = time() + $ttl;

        if ($this->db->query($this->table)->whereEq('hash', $hash)->exists()) {
            $this->db->update($this->table, compact('value', 'ttl', 'expired_time'), ['hash' => $hash]);
        } else {
            $this->db->insert($this->table, compact('hash', 'key', 'value', 'ttl', 'expired_time'));
        }
    }

    public function do_delete(string $key): void
    {
        $this->db->delete($this->table, ['hash' => md5($key)]);
    }
}