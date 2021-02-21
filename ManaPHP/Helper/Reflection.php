<?php

namespace ManaPHP\Helper;

class Reflection
{
    /**
     * @param \object $object
     * @param string  $class_name
     *
     * @return bool
     */
    public static function isInstanceOf($object, $class_name)
    {
        return is_a($object, $class_name);
    }

    /**
     * @param \object $object
     *
     * @return string
     */
    public static function getClass($object)
    {
        return get_class($object);
    }
}