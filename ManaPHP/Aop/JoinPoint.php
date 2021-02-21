<?php

namespace ManaPHP\Aop;

use ManaPHP\Exception\MisuseException;
use ReflectionMethod;

class JoinPoint
{
    /**
     * @var string
     */
    public $class;

    /**
     * @var string
     */
    public $method;

    /**
     * @var Advice
     */
    public $advice;

    /**
     * @var string
     */
    public $parent;

    /**
     * @var bool
     */
    public $is_public;

    /**
     * @var object
     */
    public $object;

    /**
     * @var array
     */
    public $args;

    /**
     * @var bool
     */
    public $invoked = false;

    /**
     * @var mixed
     */
    public $return;

    const AOP_METHOD_CALL_PARENT = '__aopCallParent';
    const AOP_METHOD_CALL_PROTECTED = '__aopCallProtected';

    /**
     * @var JoinPoint[]
     */
    protected static $_oid2obj;

    /**
     * @param string $class
     * @param string $method
     * @param string $signature
     */
    public function __construct($class, $method, $signature)
    {
        $this->class = $class;
        $this->method = $method;
        $this->advice = new Advice();

        $rm = new ReflectionMethod($class, $method);
        $parent = $rm->getDeclaringClass()->getName();
        $this->parent = $parent;
        $this->is_public = $rm->isPublic();

        $oid = spl_object_id($this);
        self::$_oid2obj[$oid] = $this;
        $signature = $signature ?? self::buildMethodSignature($parent, $method);
        $code = "return ManaPHP\Aop\JoinPoint::invokeAspect($oid, \$this, func_get_args());";

        $parents = class_parents($class);
        $base = $parents ? array_pop($parents) : $class;

        if (!$rm->isPublic() && !method_exists($class, self::AOP_METHOD_CALL_PROTECTED)) {
            runkit7_method_add(
                $base, self::AOP_METHOD_CALL_PROTECTED, function ($method, $args) {
                return $this->$method(...$args);
            }
            );
        }

        if ($class === $parent) {
            runkit7_method_rename($parent, $method, "#$method");
        } elseif (!method_exists($class, self::AOP_METHOD_CALL_PARENT)) {
            runkit7_method_add(
                $base, self::AOP_METHOD_CALL_PARENT, function (JoinPoint $joinPoint) {
                return $joinPoint->parent::{$joinPoint->method}(...$joinPoint->args);
            }
            );
        }

        runkit7_method_add($class, $method, $signature, $code, RUNKIT7_ACC_PUBLIC, $rm->getDocComment() ?: null);
    }

    /**
     * @param string $class
     * @param string $method
     *
     * @return string
     */
    protected static function buildMethodSignature($class, $method)
    {
        $signature = [];
        $rm = new ReflectionMethod($class, $method);

        foreach ($rm->getParameters() as $parameter) {
            if ($parameter->getType()) {
                $param = $parameter->getType() . ' $' . $parameter->getName();
            } else {
                $param = '$' . $parameter->getName();
            }

            if ($parameter->isDefaultValueAvailable()) {
                $param .= '=' . json_encode($parameter->getDefaultValue());
            }

            $signature[] = $param;
        }

        return implode(', ', $signature);
    }

    /**
     * @param bool $force
     *
     * @return mixed
     */
    public function invokeTarget($force = false)
    {
        if ($this->invoked) {
            if (!$force) {
                throw new MisuseException('has been invoked');
            }
        } else {
            $this->invoked = true;
        }

        if ($this->parent === $this->class) {
            if ($this->is_public) {
                return $this->return = $this->object->{"#$this->method"}(...$this->args);
            } else {
                return $this->return = $this->object->{self::AOP_METHOD_CALL_PROTECTED}("#$this->method", $this->args);
            }
        } else {
            return $this->return = $this->object->{self::AOP_METHOD_CALL_PARENT}($this);
        }
    }

    /**
     * @param int    $oid
     * @param object $object
     * @param array  $args
     *
     * @return mixed
     */
    public static function invokeAspect($oid, $object, $args)
    {
        $joinPoint = self::$_oid2obj[$oid];

        $joinPoint->object = $object;
        $joinPoint->args = $args;
        $joinPoint->return = null;
        $joinPoint->invoked = false;

        $advice = $joinPoint->advice;
        try {
            $advice->adviseBefore($joinPoint);
            $advice->adviseAround($joinPoint);
            $return = $joinPoint->invoked ? $joinPoint->return : $joinPoint->invokeTarget();
            $advice->adviseAfter($joinPoint);
        } finally {
            $joinPoint->object = null;
            $joinPoint->args = null;
            $joinPoint->return = null;
            $joinPoint->invoked = null;
        }

        return $return;
    }
}