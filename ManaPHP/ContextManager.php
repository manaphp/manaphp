<?php
namespace ManaPHP;

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
        $cid = MANAPHP_COROUTINE ? Coroutine::getCid() : -1;

        $oid = spl_object_id($object);
        if (!isset(self::$_contexts[$cid][$oid])) {
            if (!$context_class = $object->getContextClass()) {
                throw new Exception(['`:context` context class is not exists', 'context' => get_class($object) . 'Context']);
            }
            return self::$_contexts[$cid][$oid] = new $context_class;
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
            $cid = MANAPHP_COROUTINE ? Coroutine::getCid() : -1;
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