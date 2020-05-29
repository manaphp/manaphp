<?php

namespace ManaPHP;

use ManaPHP\Exception\MisuseException;

/**
 * Class Redis
 *
 * @package ManaPHP
 */
class Redis extends Component implements RedisInterface
{
    const TYPE_MASTER = 1;
    const TYPE_SLAVE = 2;

    /**
     * @var string
     */
    protected $_url;

    /**
     * @var float
     */
    protected $_timeout = 1.0;

    /**
     * @var bool
     */
    protected $_has_slave = false;

    /**
     * @var static
     */
    protected $_owner;

    /**
     * @var string
     */
    protected $_type;

    /**
     * @var \ManaPHP\Redis\Connection
     */
    protected $_connection;

    /**
     * Redis constructor.
     *
     * @param string $url
     */
    public function __construct($url = 'redis://127.0.0.1/1?timeout=3&retry_interval=0&auth=&persistent=0')
    {
        $this->_url = $url;

        if (preg_match('#timeout=([\d.]+)#', $url, $matches) === 1) {
            $this->_timeout = (float)$matches[1];
        }

        $pool_size = preg_match('#pool_size=(\d+)#', $url, $matches) ? $matches[1] : 4;

        if (strpos($url, ',') !== false) {
            $hosts = parse_url($url, PHP_URL_HOST);
            if (strpos($hosts, ',') === false) {
                $urls = explode(',', $url);
            } else {
                $urls = [];
                foreach (explode(',', $hosts) as $host) {
                    $urls[] = str_replace($hosts, $host, $url);
                }
            }

            if ($urls[0] !== '') {
                $this->poolManager->add($this, ['class' => 'ManaPHP\Redis\Connection', $urls[0]], $pool_size);
            }
            array_shift($urls);

            if (MANAPHP_COROUTINE_ENABLED) {
                shuffle($urls);

                $this->poolManager->create($this, count($urls) * $pool_size, 'slave');
                for ($i = 0; $i <= $pool_size; $i++) {
                    foreach ($urls as $u) {
                        $this->poolManager->add($this, ['class' => 'ManaPHP\Redis\Connection', $u], 1, 'slave');
                    }
                }
            } else {
                $this->poolManager->add($this, ['class' => 'ManaPHP\Redis\Connection', $urls[random_int(0, count($urls) - 1)]], 1, 'slave');
            }

            $this->_has_slave = true;
        } else {
            $this->poolManager->add($this, ['class' => 'ManaPHP\Redis\Connection', $url], $pool_size);
        }
    }

    public function __destruct()
    {
        if ($this->_owner === null) {
            $this->poolManager->remove($this);
        } else {
            $this->poolManager->push($this->_owner, $this->_connection, $this->_type);
        }
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->_url;
    }

    /**
     * @param string $method
     *
     * @return string
     */
    protected function _getConnectionType(string $method)
    {
        static $map;

        if ($map === null) {
            $map = array_fill_keys(get_class_methods('Redis'), self::TYPE_MASTER);

            /** @noinspection SpellCheckingInspection */
            unset($map['__construct'], $map['__destruct'], $map['_prefix'], $map['_serialize'], $map['_unserialize'],
                $map['auth'], $map['bgSave'], $map['bgrewriteaof'], $map['clearLastError'], $map['client'],
                $map['close'], $map['command'], $map['config'], $map['connect'], $map['debug'], $map['echo'], $map['getAuth'],
                $map['getHost'], $map['getLastError'], $map['getMode'], $map['getOption'], $map['getPersistentID'],
                $map['getPort'], $map['isConnected'], $map['migrate'], $map['pconnect'], $map['ping'], $map['role'],
                $map['info'], $map['lastSave'], $map['eval'], $map['evalsha'], $map['exec'],
                $map['getReadTimeout'], $map['getTimeout'], $map['rawcommand'], $map['script'],
                $map['select'], $map['slaveof'], $map['slowlog'], $map['time'], $map['evaluate'], $map['evaluateSha'],
                $map['open'], $map['popen'], $map['multi'], $map['pipeline'], $map['discard']);

            /** @noinspection SpellCheckingInspection */
            $read_ops = ['bitcount', 'bitop', 'bitpos', 'dbSize', 'dump', 'exists'
                ,'geodist','geohash','geopos','georadius','georadius_ro', 'georadiusbymember','georadiusbymember_ro'
                ,'get','getBit','getDBNum','getRange'
                ,'hExists','hGet','hGetAll', 'hKeys','hLen','hMget','hStrLen','hVals','hscan'
                ,'keys','lLen','lindex','lrange','mget','object','pfcount'
                ,'sDiff','sInter','sMembers','sRandMember','sUnion','scan','scard','sismember','sscan'
                ,'strlen','ttl','type','zCard','zCount','zLexCount','zRange','zRangeByLex','zRangeByScore'
                ,'zRank','zRevRange','zRevRangeByLex','zRevRangeByScore','zRevRank','zScore'
                ,'zscan','getKeys','getMultiple','lGet','lGetRange','lSize'
                ,'sContains','sGetMembers','sSize','substr','zSize'];

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
            if ($this->_connection !== null) {
                $this->_connection->call($method, $arguments);
                return $this;
            } else {
                if ($this->_has_slave) {
                    throw new MisuseException("slave is exists, `$method` method only can be used on instance that created by calling getMaster or getSlave");
                }

                $master = $this->getMaster();
                $master->call($method, $arguments);

                return $master;
            }
        } elseif ($method === 'watch') {
            if ($this->_connection !== null) {
                $this->_connection->call($method, $arguments);
                return null;
            } else {
                throw new MisuseException('`watch` method only can be used on instance that created by calling getMaster or getSlave');
            }
        } elseif ($this->_connection !== null) {
            return $this->_connection->call($method, $arguments);
        } else {
            $type = $this->_has_slave ? $this->_getConnectionType($method) : 'default';

            $connection = $this->poolManager->pop($this, $this->_timeout, $type);

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
        $this->fireEvent('redis:calling', ['method' => $method, 'arguments' => $arguments]);

        $r = $this->call($method, $arguments);

        $this->fireEvent('redis:called', ['method' => $method, 'arguments' => $arguments, 'return' => $r]);

        return $r;
    }

    /**
     * @return static
     */
    public function getMaster()
    {
        if ($this->_owner !== null) {
            throw new MisuseException('getMaster does\'t support nesting.');
        }

        $clone = clone $this;

        $clone->_owner = $this;
        $clone->_type = 'default';
        $clone->_connection = $this->poolManager->pop($this, $this->_timeout, $clone->_type);

        return $clone;
    }

    /**
     * @return static
     */
    public function getSlave()
    {
        if ($this->_owner !== null) {
            throw new MisuseException('getSlave does\'t support nesting.');
        }

        $clone = clone $this;

        $clone->_owner = $this;
        $clone->_type = $this->_has_slave ? 'slave' : 'default';
        $clone->_connection = $this->poolManager->pop($this, $this->_timeout, $clone->_type);

        return $clone;
    }

    public function dump()
    {
        $data = parent::dump();

        unset($data['_owner'], $data['_type'], $data['_connection']);

        return $data;
    }
}
