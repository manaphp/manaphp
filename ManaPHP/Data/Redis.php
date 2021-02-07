<?php

namespace ManaPHP\Data;

use ManaPHP\Component;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NonCloneableException;
use ManaPHP\Exception\NotSupportedException;

/**
 * @property-read \ManaPHP\Pool\ManagerInterface $poolManager
 */
class Redis extends Component implements RedisInterface
{
    const TYPE_MASTER = 1;
    const TYPE_SLAVE = 2;

    /**
     * @var string
     */
    protected $uri;

    /**
     * @var float
     */
    protected $timeout = 1.0;

    /**
     * @var bool
     */
    protected $has_slave = false;

    /**
     * @var string
     */
    protected $pool_size = '4';

    /**
     * @var static
     */
    protected $owner;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var \ManaPHP\Data\Redis\Connection
     */
    protected $connection;

    /**
     * @param string $uri
     */
    public function __construct($uri = 'redis://127.0.0.1/1?timeout=3&retry_interval=0&auth=&persistent=0')
    {
        $this->uri = $uri;

        if (preg_match('#timeout=([\d.]+)#', $uri, $matches) === 1) {
            $this->timeout = (float)$matches[1];
        }

        if (preg_match('#pool_size=([\d/]+)#', $uri, $matches)) {
            $this->pool_size = $matches[1];
        }

        if (($pos = strpos($this->pool_size, '/')) === false) {
            $master_pool_size = (int)$this->pool_size;
            $slave_pool_size = (int)$this->pool_size;
        } else {
            $master_pool_size = (int)substr($this->pool_size, 0, $pos);
            $slave_pool_size = (int)substr($this->pool_size, $pos + 1);
        }

        $uris = [];
        if (str_contains($uri, '{') && preg_match('#{[^}]+}#', $uri, $matches)) {
            $hosts = $matches[0];
            foreach (explode(',', substr($hosts, 1, -1)) as $value) {
                $value = trim($value);
                $uris[] = $value === '' ? $value : str_replace($hosts, $value, $uri);
            }
        } elseif (str_contains($uri, ',')) {
            $hosts = parse_url($uri, PHP_URL_HOST);
            if (str_contains($hosts, ',')) {
                foreach (explode(',', $hosts) as $value) {
                    $value = trim($value);
                    $uris[] = $value === '' ? $value : str_replace($hosts, $value, $uri);
                }
            } else {
                foreach (explode(',', $uri) as $value) {
                    $uris[] = trim($value);
                }
            }
        } else {
            $uris[] = $uri;
        }

        if ($uris[0] !== '') {
            $this->poolManager->add($this, ['class' => 'ManaPHP\Data\Redis\Connection', $uris[0]], $master_pool_size);
        }

        if (count($uris) > 1) {
            array_shift($uris);

            if (MANAPHP_COROUTINE_ENABLED) {
                shuffle($uris);

                $this->poolManager->create($this, count($uris) * $slave_pool_size, 'slave');
                for ($i = 0; $i <= $slave_pool_size; $i++) {
                    foreach ($uris as $u) {
                        $this->poolManager->add($this, ['class' => 'ManaPHP\Data\Redis\Connection', $u], 1, 'slave');
                    }
                }
            } else {
                $u = $uris[random_int(0, count($uris) - 1)];
                $this->poolManager->add($this, ['class' => 'ManaPHP\Data\Redis\Connection', $u], 1, 'slave');
            }

            $this->has_slave = true;
        }
    }

    public function __destruct()
    {
        if ($this->owner === null) {
            $this->poolManager->remove($this);
        } else {
            $this->poolManager->push($this->owner, $this->connection, $this->type);
        }
    }

    public function __clone()
    {
        throw new NonCloneableException($this);
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @param string $method
     *
     * @return string
     */
    protected function getConnectionType(string $method)
    {
        static $map;

        if ($map === null) {
            if (!class_exists('Redis')) {
                throw new NotSupportedException('Redis class is not exists');
            }

            $map = array_fill_keys(get_class_methods('Redis'), self::TYPE_MASTER);

            /** @noinspection SpellCheckingInspection */
            unset(
                $map['__construct'], $map['__destruct'], $map['_prefix'], $map['_serialize'], $map['_unserialize'],
                $map['auth'], $map['bgSave'], $map['bgrewriteaof'], $map['clearLastError'], $map['client'],
                $map['close'], $map['command'], $map['config'], $map['connect'], $map['debug'], $map['echo'],
                $map['getAuth'],
                $map['getHost'], $map['getLastError'], $map['getMode'], $map['getOption'], $map['getPersistentID'],
                $map['getPort'], $map['isConnected'], $map['migrate'], $map['pconnect'], $map['ping'], $map['role'],
                $map['info'], $map['lastSave'], $map['eval'], $map['evalsha'], $map['exec'],
                $map['getReadTimeout'], $map['getTimeout'], $map['rawcommand'], $map['script'],
                $map['select'], $map['slaveof'], $map['slowlog'], $map['time'], $map['evaluate'], $map['evaluateSha'],
                $map['open'], $map['popen'], $map['multi'], $map['pipeline'], $map['discard']
            );

            /** @noinspection SpellCheckingInspection */
            $read_ops = ['bitcount', 'bitop', 'bitpos', 'dbSize', 'dump', 'exists'
                         , 'geodist', 'geohash', 'geopos', 'georadius', 'georadius_ro', 'georadiusbymember',
                         'georadiusbymember_ro'
                         , 'get', 'getBit', 'getDBNum', 'getRange'
                         , 'hExists', 'hGet', 'hGetAll', 'hKeys', 'hLen', 'hMget', 'hStrLen', 'hVals', 'hscan'
                         , 'keys', 'lLen', 'lindex', 'lrange', 'mget', 'object', 'pfcount'
                         , 'sDiff', 'sInter', 'sMembers', 'sRandMember', 'sUnion', 'scan', 'scard', 'sismember', 'sscan'
                         , 'strlen', 'ttl', 'type', 'zCard', 'zCount', 'zLexCount', 'zRange', 'zRangeByLex',
                         'zRangeByScore'
                         , 'zRank', 'zRevRange', 'zRevRangeByLex', 'zRevRangeByScore', 'zRevRank', 'zScore'
                         , 'zscan', 'getKeys', 'getMultiple', 'lGet', 'lGetRange', 'lSize'
                         , 'sContains', 'sGetMembers', 'sSize', 'substr', 'zSize'];

            foreach ($read_ops as $item) {
                $map[$item] = self::TYPE_SLAVE;
            }

            $map = array_change_key_case($map, CASE_LOWER);
        }

        if ($type = $map[strtolower($method)] ?? false) {
            return $type === self::TYPE_SLAVE ? 'slave' : 'default';
        } else {
            throw new MisuseException("`$method` method is ambiguity");
        }
    }

    /**
     * @param string $method
     * @param array  $arguments
     *
     * @return mixed
     */
    public function call($method, $arguments)
    {
        if ($method === 'multi' || $method === 'pipeline') {
            if ($this->connection !== null) {
                $this->connection->call($method, $arguments);
                return $this;
            } else {
                if ($this->has_slave) {
                    throw new MisuseException(
                        "slave is exists, `$method` method only can be used on instance that created by calling getMaster or getSlave"
                    );
                }

                $master = $this->getMaster();
                $master->connection->call($method, $arguments);

                return $master;
            }
        } elseif ($method === 'watch') {
            if ($this->connection !== null) {
                $this->connection->call($method, $arguments);
                return null;
            } else {
                throw new MisuseException(
                    '`watch` method only can be used on instance that created by calling getMaster or getSlave'
                );
            }
        } elseif ($this->connection !== null) {
            return $this->connection->call($method, $arguments);
        } else {
            $type = $this->has_slave ? $this->getConnectionType($method) : 'default';

            $connection = $this->poolManager->pop($this, $this->timeout, $type);

            try {
                return $connection->call($method, $arguments);
            } finally {
                $this->poolManager->push($this, $connection, $type);
            }
        }
    }

    /**
     * @param string $method
     * @param array  $arguments
     *
     * @return bool|mixed
     */
    public function __call($method, $arguments)
    {
        $this->fireEvent('redis:calling', compact('method', 'arguments'));

        $return = $this->call($method, $arguments);

        $this->fireEvent('redis:called', compact('method', 'arguments', 'return'));

        return $return;
    }

    /**
     * @return static
     */
    public function getMaster()
    {
        if ($this->owner !== null) {
            throw new MisuseException('getMaster does\'t support nesting.');
        }

        $clone = clone $this;

        $clone->owner = $this;
        $clone->type = 'default';
        $clone->connection = $this->poolManager->pop($this, $this->timeout, $clone->type);

        return $clone;
    }

    /**
     * @return static
     */
    public function getSlave()
    {
        if ($this->owner !== null) {
            throw new MisuseException('getSlave does\'t support nesting.');
        }

        $clone = clone $this;

        $clone->owner = $this;
        $clone->type = $this->has_slave ? 'slave' : 'default';
        $clone->connection = $this->poolManager->pop($this, $this->timeout, $clone->type);

        return $clone;
    }

    public function dump()
    {
        $data = parent::dump();

        unset($data['owner'], $data['type'], $data['connection']);

        return $data;
    }
}
