<?php

namespace ManaPHP\Aop;

class Advice implements Unaspectable
{
    /**
     * @var callable[]
     */
    protected $_before = [];

    /**
     * @var callable[]
     */
    protected $_around = [];

    /**
     * @var callable[]
     */
    protected $_after = [];

    /**
     * @param callable $closure
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
     * @param callable $closure
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
     * @param callable $closure
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
            $closure($joinPoint);
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
            $closure($joinPoint);
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
            $closure($joinPoint);
        }
    }
}