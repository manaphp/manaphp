<?php

namespace ManaPHP\Aop;

use Closure;

class Advice implements Unaspectable
{
    /**
     * @var Closure[]
     */
    protected $_before = [];

    /**
     * @var Closure
     */
    protected $_around = [];

    /**
     * @var Closure[]
     */
    protected $_after = [];

    /**
     * @param \Closure $closure
     *
     * @return static
     */
    public function addAround($closure = null)
    {
        if ($closure !== null) {
            $this->_around[] = $closure;
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
            $this->_before[] = $closure;
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
            $this->_after[] = $closure;
        }

        return $this;
    }

    /**
     * @param \ManaPHP\Aop\JoinPoint $joinPoint
     *
     * @return void
     */
    public function adviseBefore($joinPoint)
    {
        foreach ($this->_before as $closure) {
            if (is_array($closure)) {
                list($object, $method) = $closure;
                $object->$method($joinPoint);
            } else {
                $closure($joinPoint);
            }
        }
    }

    /**
     * @param \ManaPHP\Aop\JoinPoint $joinPoint
     *
     * @return void
     */
    public function adviseAfter($joinPoint)
    {
        foreach ($this->_after as $closure) {
            if (is_array($closure)) {
                list($object, $method) = $closure;
                $object->$method($joinPoint);
            } else {
                $closure($joinPoint);
            }
        }
    }

    /**
     * @param \ManaPHP\Aop\JoinPoint $joinPoint
     *
     * @return void
     */
    public function adviseAround($joinPoint)
    {
        foreach ($this->_around as $closure) {
            if (is_array($closure)) {
                list($object, $method) = $closure;
                $object->$method($joinPoint);
            } else {
                $closure($joinPoint);
            }
        }
    }
}