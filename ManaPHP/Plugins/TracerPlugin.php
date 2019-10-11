<?php
namespace ManaPHP\Plugins;

use ManaPHP\Plugin;

/**
 * Class TracerPlugin
 * @package ManaPHP\Plugins
 */
class TracerPlugin extends Plugin
{
    public function __construct()
    {
        $this->eventsManager->attachEvent('redis:calling', [$this, 'onRedisCalling']);
        $this->eventsManager->attachEvent('redis:called', [$this, 'onRedisCalled']);
    }

    public function onRedisCalling(/** @noinspection PhpUnusedParameterInspection */ $redis, $data)
    {
        $name = $data['name'];
        $arguments = $data['arguments'];

        if (stripos(',blPop,brPop,brpoplpush,subscribe,psubscribe,', ",$name,") !== false) {
            $this->logger->debug(["\$redis->$name(:args) ... blocking",
                'args' => substr(@json_encode($arguments, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 1, -1),
            ], 'redis.' . $name);
        }
    }

    public function onRedisCalled(/** @noinspection PhpUnusedParameterInspection */ $redis, $data)
    {
        $name = $data['name'];
        $arguments = $data['arguments'];
        $r = $data['return'];

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
    }
}