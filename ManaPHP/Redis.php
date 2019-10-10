<?php
namespace ManaPHP;

use ManaPHP\Coroutine\Context\Inseparable;

class RedisContext implements Inseparable
{
    /**
     * @var \ManaPHP\Redis\Connection
     */
    public $connection;
}

/**
 * Class Redis
 * @package ManaPHP
 * @property-read \ManaPHP\RedisContext $_context
 */
class Redis extends Component
{
    /**
     * @var string
     */
    protected $_uri;

    /**
     * @var int
     */
    protected $_pool_size;

    /**
     * @var float
     */
    protected $_timeout = 1.0;

    /**
     * Redis constructor.
     *
     * @param string|\ManaPHP\Redis\Connection $uri
     */
    public function __construct($uri = 'redis://127.0.0.1/1?timeout=3&retry_interval=0&auth=&persistent=0')
    {
        if (is_string($uri)) {
            $this->_uri = $uri;
            $pool_size = preg_match('#pool_size=(\d+)#', $uri, $matches) ? $matches[1] : 4;
            $connection = ['class' => 'ManaPHP\Redis\Connection', $this->_uri];
        } else {
            $pool_size = 1;
            $connection = $uri;
            $this->_uri = $uri->getUri();
        }

        if (strpos($this->_uri, 'timeout=') !== false && preg_match('#timeout=([\d.]+)#', $this->_uri, $matches) === 1) {
            $this->_timeout = (float)$matches[1];
        }

        $this->_pool_size = $pool_size;

        $this->poolManager->add($this, $connection, $pool_size);
    }

    public function __destruct()
    {
        $this->poolManager->remove($this);
    }

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return bool|mixed
     */
    public function __call($name, $arguments)
    {
        $context = $this->_context;

        if (stripos(',blPop,brPop,brpoplpush,subscribe,psubscribe,', ",$name,") !== false) {
            $this->logger->debug(["\$redis->$name(:args) ... blocking",
                'args' => substr(@json_encode($arguments, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 1, -1),
            ], 'redis.' . $name);
        }

        if ($context->connection) {
            $connection = $context->connection;
        } else {
            $connection = $this->poolManager->pop($this, $this->_timeout);
        }

        try {
            $r = $connection->call($name, $arguments);
        } finally {
            if (!$context->connection) {
                $this->poolManager->push($this, $connection);
            }
        }

        if ($name === 'get' && strpos($arguments[0], ':cache:') !== false) {
            if ($r === false) {
                $this->logger->info("\$redis->get(\"$arguments[0]\") => false", 'redis.cache.get');
            } else {
                $this->logger->debug("\$redis->get(\"$arguments[0]\") => " . (strlen($r) > 64 ? substr($r, 0, 64) . '...' : $r), 'redis.cache.get');
            }
        } /** @noinspection NotOptimalIfConditionsInspection */
        elseif ($name === 'set' && strpos($arguments[0], ':cache:') !== false) {
            $args = $arguments;
            if (strlen($args[1]) > 64) {
                $args[1] = substr($args[1], 0, 64) . '...';
            }
            $this->logger->info(["\$redis->$name(:args) => :return",
                'args' => substr(@json_encode($args, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 1, -1),
                'return' => @json_encode($r, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ], 'redis.cache.set');
        } /** @noinspection SpellCheckingInspection */
        elseif (stripos(',_prefix,_serialize,_unserialize,auth,bitcount,bitop,bitpos,clearLastError,client,close,connect,dbSize,debug,
                    ,dump,echo,exists,expireAt,geodist,geohash,geopos,georadius,georadiusbymember,get,getBit,getDbNum,
                    ,getHost,getKeys,getLastError,getMode,getMultiple,getOption,getPersistentID,getPort,getRange,getReadTimeout,
                    ,getTimeout,hExists,hGet,hGetAll,hKeys,hLen,hMget,hStrLen,hVals,hscan,info,isConnected,lGet,lGetRange,
                    ,lSize,lastSave,object,pconnect,persist,pexpire,pexpireAt,pfcount,ping,pttl,randomKey,role,sContains,sDiff,
                    ,sInter,sMembers,sRandMember,sSize,sUnion,scan,select,setTimeout,slowlog,sort,sortAsc,sortAscAlpha,sortDesc,sortDescAlpha,
                    sscan,strlen,time,ttl,type,zCard,zCount,zInter,zLexCount,zRange,zRangeByLex,zRangeByScore,zRank,zRevRange,
                    ,zRevRangeByLex,zRevRangeByScore,zRevRank,zScore,zUnion,zscan,expire,keys,lLen,lindex,lrange,mget,open,popen,
                    ,sGetMembers,scard,sendEcho,sismember,substr,zReverseRange,zSize,', ",$name,") !== false) {
            $this->logger->debug(["\$redis->$name(:args) => :return",
                'args' => substr(@json_encode($arguments, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 1, -1),
                'return' => @json_encode($r, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ], 'redis.' . $name);
        } else {
            $this->logger->info(["\$redis->$name(:args) => :return",
                'args' => substr(@json_encode($arguments, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 1, -1),
                'return' => @json_encode($r, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            ], 'redis.' . $name);
        }

        return $r;
    }
}