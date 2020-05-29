<?php

namespace ManaPHP;

use Closure;
use ManaPHP\Exception\MisuseException;

class RedisContext
{
    /**
     * @var bool
     */
    public $in_do = false;

    /**
     * @var \ManaPHP\Redis\Connection
     */
    public $connection;
}

/**
 * Class Redis
 *
 * @package ManaPHP
 * @property-read \ManaPHP\RedisContext $_context
 */
class Redis extends Component
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
        $this->poolManager->remove($this);
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
     * @param int      $type
     * @param callable $func
     *
     * @return mixed
     */
    public function do($type, $func)
    {
        $context = $this->_context;

        if ($context->in_do) {
            throw new MisuseException('do is called nesting.');
        } elseif ($context->connection !== null) {
            $this->poolManager->push($this, $context->connection);
            $context->connection = null;
            throw new MisuseException('redis is connected already.');
        }

        if ($this->_has_slave) {
            $connection_type = $type === self::TYPE_SLAVE ? 'slave' : 'default';
        } else {
            $connection_type = 'default';
        }

        $context->connection = $this->poolManager->pop($this, $this->_timeout, $connection_type);
        $context->in_do = true;
        try {
            if ($func instanceof Closure) {
                return $func($this);
            } else {
                list($object, $method) = $func;
                return $object->$method($this);
            }
        } finally {
            $this->poolManager->push($this, $context->connection, $connection_type);
            $context->in_do = false;
            $context->connection = null;
        }
    }

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return bool|mixed
     */
    public function call($name, $arguments)
    {
        $context = $this->_context;

        if ($context->in_do) {
            return $context->connection->call($name, $arguments);
        } elseif ($name === 'multi' || $name === 'pipeline') {
            if ($this->_has_slave) {
                throw new MisuseException('use `do` when has slave');
            }

            if ($context->connection !== null) {
                $this->poolManager->push($this, $context->connection);
                $context->connection = null;
                throw new MisuseException('redis is in multi already.');
            }

            $context->connection = $this->poolManager->pop($this, $this->_timeout);

            try {
                /** @noinspection PhpUnusedLocalVariableInspection */
                $success = false;
                $context->connection->call($name, $arguments);
                $success = true;
            } finally {
                if (!$success) {
                    $this->poolManager->push($this, $context->connection);
                    $context->connection = null;
                }
            }

            return $this;
        } elseif ($name === 'exec' || $name === 'discard') {
            if ($context->connection === null) {
                throw new MisuseException('redis is not in multi.');
            }

            try {
                return $context->connection->call($name, $arguments);
            } finally {
                $this->poolManager->push($this, $context->connection);
                $context->connection = null;
            }
        } elseif ($context->connection) {
            try {
                /** @noinspection PhpUnusedLocalVariableInspection */
                $success = false;
                $context->connection->call($name, $arguments);
                $success = true;
            } finally {
                if (!$success) {
                    $this->poolManager->push($this, $context->connection);
                    $context->connection = null;
                }
            }

            return $this;
        } else {
            $type = $this->_has_slave ? $this->_getConnectionType($name) : 'default';

            $connection = $this->poolManager->pop($this, $this->_timeout, $type);

            try {
                return $connection->call($name, $arguments);
            } finally {
                $this->poolManager->push($this, $connection, $type);
            }
        }
    }

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return bool|mixed
     */
    public function __call($name, $arguments)
    {
        $this->fireEvent('redis:calling', ['name' => $name, 'arguments' => $arguments]);

        $r = $this->call($name, $arguments);

        $this->fireEvent('redis:called', ['name' => $name, 'arguments' => $arguments, 'return' => $r]);

        return $r;
    }
}
