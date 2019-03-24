<?php
namespace ManaPHP;

class ContextManager
{
    /**
     * @var bool
     */
    protected static $_use_dynamic = false;

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
        $cid = \Swoole\Coroutine::getuid();

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
     * @param bool $dynamic
     */
    public static function useDynamic($dynamic = true)
    {
        self::$_use_dynamic = $dynamic;
    }

    /**
     * @return bool
     */
    public static function isUseDynamic()
    {
        return self::$_use_dynamic;
    }

    public static function reset()
    {
        $cid = \Swoole\Coroutine::getuid();

        self::$_contexts[$cid] = [];
    }
}