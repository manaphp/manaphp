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
    protected static $_configure = [];

    /**
     * @var array
     */
    protected static $_contexts = [];

    /**
     * @param string $component
     * @param string t$context
     */
    public static function configure($component, $context)
    {
        self::$_configure[$component] = $context;
    }

    /**
     * @param $object
     *
     * @return mixed
     */
    public static function get($object)
    {
        $id = spl_object_id($object);
        if (!isset(self::$_contexts[$id])) {
            return self::$_contexts[$id] = new self::$_configure[get_class($object)];
        }
        return self::$_contexts[$id];
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
        self::$_contexts = [];
    }
}