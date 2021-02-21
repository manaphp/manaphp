<?php

namespace ManaPHP\Helper;

use ManaPHP\Aop\ProxyInterface;

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
        return is_a($object instanceof ProxyInterface ? $object->getTargetObject() : $object, $class_name);
    }

    /**
     * @param \object $object
     *
     * @return string
     */
    public static function getClass($object)
    {
        return get_class($object instanceof ProxyInterface ? $object->getTargetObject() : $object);
    }

    /**
     * @param \object $object
     *
     * @return array
     */
    public static function getObjectVars($object)
    {
        return get_object_vars($object instanceof ProxyInterface ? $object->getTargetObject() : $object);
    }
}