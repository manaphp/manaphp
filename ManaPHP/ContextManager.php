<?php
namespace ManaPHP;

use ManaPHP\Coroutine\Context\Inseparable;
use Swoole\Coroutine;

class ContextManager
{
    /**
     * @var array
     */
    protected static $_contexts = [];

    /**
     * @param \ManaPHP\Component $object
     *
     * @return mixed
     */
    public static function get($object)
    {
        $cid = MANAPHP_COROUTINE_ENABLED ? Coroutine::getCid() : -1;

        $oid = spl_object_id($object);
        if (!isset(self::$_contexts[$cid][$oid])) {
            if ($root_cid = self::$_contexts[$cid]['root_cid'] ?? null) {
                if ($context = self::$_contexts[$root_cid][$oid] ?? null) {
                    return self::$_contexts[$cid][$oid] = $context instanceof Inseparable ? $object->createContext() : $context;
                } else {
                    $context = self::$_contexts[$cid][$oid] = $object->createContext();
                    if (!$context instanceof Inseparable) {
                        self::$_contexts[$root_cid][$oid] = $context;
                    }
                    return $context;
                }
            } else {
                return self::$_contexts[$cid][$oid] = $object->createContext();
            }
        } else {
            return self::$_contexts[$cid][$oid];
        }
    }

    /**
     * @param int $cid
     */
    public static function reset($cid = null)
    {
        if ($cid === null) {
            $cid = MANAPHP_COROUTINE_ENABLED ? Coroutine::getCid() : -1;
        }

        unset(self::$_contexts[$cid]);
    }

    /**
     * @param int $old
     * @param int $new
     */
    public static function move($old, $new)
    {
        self::$_contexts[$new] = self::$_contexts[$old];
        unset(self::$_contexts[$old]);
    }

    /**
     * @param int $old
     * @param int $new
     */
    public static function clones($old, $new)
    {
        foreach (self::$_contexts[$old] as $k => $v) {
            self::$_contexts[$new][$k] = clone $v;
        }
    }
}