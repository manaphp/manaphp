<?php
namespace ManaPHP\Aop;

class JoinPoint
{
    public $class;
    public $method;
    public $object;
    public $args;
    public $return;
    public $advice;

    public function __construct($class, $method)
    {
        $this->class = $class;
        $this->method = $method;
        $this->advice = new Advice();
    }

    public function invokeAspect($object, $args)
    {
        $copy = clone $this;

        $copy->object = $object;
        $copy->args = $args;

        $advice = $this->advice;

        foreach ($advice->before as $closure) {
            if (is_array($closure)) {
                list($object, $method) = $closure;
                $object->$method($copy);
            } else {
                $closure($copy);
            }
        }

        if ($advice->around) {
            $closure = $advice->around;
            if (is_array($closure)) {
                list($object, $method) = $closure;
                $object->$method($copy);
            } else {
                $closure($copy);
            }
        } else {
            $copy->return = $copy->object->{'#' . $copy->method}(...$copy->args);
        }

        foreach ($advice->after as $closure) {
            if (is_array($closure)) {
                list($object, $method) = $closure;
                $object->$method($copy);
            } else {
                $closure($copy);
            }
        }

        return $copy->return;
    }

    /**
     * @param \Closure $closure
     *
     * @return static
     */
    public function addAround($closure = null)
    {
        if ($closure !== null) {
            $this->advice->around[] = $closure;
        }

        return $this;
    }

    /**
     * @param \Closure $closure
     *
     * @return static
     */
    public function addBefore($closure = null)
    {
        if ($closure !== null) {
            $this->advice->before[] = $closure;
        }

        return $this;
    }

    /**
     * @param \Closure $closure
     *
     * @return static
     */
    public function addAfter($closure = null)
    {
        if ($closure !== null) {
            $this->advice->after[] = $closure;
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function invokeTarget()
    {
        return $this->return = $this->object->{'#' . $this->method}(...$this->args);
    }
}