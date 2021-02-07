<?php

namespace ManaPHP\Aop;

class Advice implements Unaspectable
{
    /**
     * @var callable[]
     */
    protected $before = [];

    /**
     * @var callable[]
     */
    protected $around = [];

    /**
     * @var callable[]
     */
    protected $after = [];

    /**
     * @param callable $closure
     *
     * @return static
     */
    public function addAround($closure = null)
    {
        if ($closure !== null) {
            $this->around[] = $closure;
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
            $this->before[] = $closure;
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
            $this->after[] = $closure;
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
        foreach ($this->before as $closure) {
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
        foreach ($this->after as $closure) {
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
        foreach ($this->around as $closure) {
            $closure($joinPoint);
        }
    }
}