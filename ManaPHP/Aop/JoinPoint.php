<?php

namespace ManaPHP\Aop;

class JoinPoint implements JoinPointInterface
{
    /**
     * @var object
     */
    public $target;

    /**
     * @var string
     */
    public $method;

    /**
     * @var Advice[]
     */
    public $advices;

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

    /**
     * @param string                $target
     * @param string                $method
     * @param \ManaPHP\Aop\Advice[] $advices
     */
    public function __construct($target, $method, $advices)
    {
        $this->target = $target;
        $this->method = $method;
        $this->advices = $advices;
    }

    /**
     * @param bool $force
     *
     * @return mixed
     */
    public function invokeTarget($force = false)
    {
        if (!$this->invoked || $force) {
            $this->invoked = true;
            $target = $this->target;
            $this->return = $target->{$this->method}(...$this->args);
        }
    }

    /**
     * @param array $args
     *
     * @return mixed
     */
    public function invoke($args)
    {
        $this->args = $args;

        foreach ($this->advices as $advice) {
            $advice->adviseBefore($this);
        }

        foreach ($this->advices as $advice) {
            $advice->adviseAround($this);
        }

        if (!$this->invoked) {
            $this->invokeTarget();
        }

        foreach ($this->advices as $advice) {
            $advice->adviseAfter($this);
        }

        return $this->return;
    }
}