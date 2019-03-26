<?php
namespace ManaPHP;

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
        $cid = MANAPHP_COROUTINE ? \Swoole\Coroutine::getuid() : -1;

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

    public static function reset()
    {
        $cid = MANAPHP_COROUTINE ? \Swoole\Coroutine::getuid() : -1;

        self::$_contexts[$cid] = [];
    }
}