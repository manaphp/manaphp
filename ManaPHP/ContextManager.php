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
     * @param $object
     *
     * @return mixed
     */
    public static function get($object)
    {
        $id = spl_object_id($object);
        if (!isset(self::$_contexts[$id])) {
            $object_class = get_class($object);
            if (!isset(self::$_configure[$object_class])) {
                $context_class = null;
                $parent_class = $object_class;
                do {
                    $try = $parent_class . 'Context';
                    if (class_exists($try, false)) {
                        $context_class = $try;
                        break;
                    }
                } while ($parent_class = get_parent_class($parent_class));

                if (!$context_class) {
                    throw new Exception(['`:context` context class is not exists', 'context' => $object_class . 'Context']);
                }
                self::$_configure[$object_class] = $context_class;
            }

            return self::$_contexts[$id] = new self::$_configure[$object_class];
        } else {
            return self::$_contexts[$id];
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
        self::$_contexts = [];
    }
}