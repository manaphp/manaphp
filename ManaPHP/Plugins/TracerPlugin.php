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

        $this->eventsManager->attachEvent('db:connect', [$this, 'onDbConnect']);
        $this->eventsManager->attachEvent('db:queried', [$this, 'onDbQueried']);
        $this->eventsManager->attachEvent('db:executed', [$this, 'onDbExecuted']);
        $this->eventsManager->attachEvent('db:inserted', [$this, 'onDbInserted']);
        $this->eventsManager->attachEvent('db:begin', [$this, 'onDbBegin']);
        $this->eventsManager->attachEvent('db:rollback', [$this, 'onDbRollback']);
        $this->eventsManager->attachEvent('db:commit', [$this, 'onDbCommit']);
        $this->eventsManager->attachEvent('db:metadata', [$this, 'onDbMetadata']);
        $this->eventsManager->attachEvent('db:abnormal', [$this, 'onDbAbnormal']);

        $this->eventsManager->attachEvent('mailer:sending', [$this, 'onMailerSending']);
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

    public function onDbConnect(/** @noinspection PhpUnusedParameterInspection */ $db, $data)
    {
        $this->logger->debug(['connect to `:dsn`', 'dsn' => $data['dsn']], 'db.connect');
    }

    public function onDbExecuted(/** @noinspection PhpUnusedParameterInspection */ $db, $data)
    {
        $this->logger->info($data, 'db.' . $data['type']);
    }

    public function onDbQueried(/** @noinspection PhpUnusedParameterInspection */ $db, $data)
    {
        $this->logger->debug($data, 'db.query');
    }

    public function onDbInserted(/** @noinspection PhpUnusedParameterInspection */ $db, $data)
    {
        $this->logger->info($data, 'db.insert');
    }

    public function onDbBegin(/** @noinspection PhpUnusedParameterInspection */ $db, $data)
    {
        $this->logger->info('transaction begin', 'db.begin');
    }

    public function onDbRollback()
    {
        $this->logger->info('transaction rollback', 'db.rollback');
    }

    public function onDbCommit()
    {
        $this->logger->info('transaction commit', 'db.commit');
    }

    public function onDbMetadata(/** @noinspection PhpUnusedParameterInspection */ $db, $data)
    {
        $this->logger->debug($data, 'db.metadata');
    }

    public function onDbAbnormal()
    {
        $this->logger->error('transaction is not close correctly', 'db.abnormal');
    }

    public function onMailerSending($mailer, $data)
    {
        /** @var \ManaPHP\Mailer\Message $message */
        $message = $data['message'];
        $this->logger->debug(['From: ', $message->getFrom()]);
        $this->logger->debug(['To: ', $message->getTo()]);
        $this->logger->debug(['Cc:', $message->getCc()]);
        $this->logger->debug(['Bcc: ', $message->getBcc()]);
        $this->logger->debug(['Subject: ', $message->getSubject()]);
    }
}