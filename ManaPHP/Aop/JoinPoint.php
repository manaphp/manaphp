<?php

namespace ManaPHP\Aop;

use Throwable;
use ManaPHP\Aop\JoinPoint\MissingCallProcessException;
use ManaPHP\Aop\JoinPoint\ProceedException;

class JoinPoint
{
    /**
     * @var object
     */
    protected $target;

    /**
     * @var string
     */
    protected $method;

    /**
     * @var \ManaPHP\Aop\Advisor[][]
     */
    protected $advisors;

    /**
     * @var array
     */
    protected $args;

    /**
     * @var bool
     */
    protected $invoked = false;

    /**
     * @var mixed
     */
    protected $return;

    /**
     * @var \Throwable
     */
    protected $exception;

    /**
     * @param string                   $target
     * @param string                   $method
     * @param \ManaPHP\Aop\Advisor[][] $advisors
     */
    public function __construct($target, $method, $advisors)
    {
        $this->target = $target;
        $this->method = $method;
        $this->advisors = $advisors;
    }

    /**
     * @return mixed|\Throwable
     */
    public function proceed()
    {
        if (($advisors = $this->advisors[Advisor::ADVICE_BEFORE] ?? null) !== null) {
            foreach ($advisors as $advisor) {
                $advisor->advise($this);
            }
        }

        if ($this->invoked) {
            throw new ProceedException($this->method);
        }

        $this->invoked = true;
        $target = $this->target;
        if (isset($this->advisors[Advisor::ADVICE_AFTER_THROWING])) {
            try {
                return $this->return = $target->{$this->method}(...$this->args);
            } catch (Throwable $exception) {
                return $this->exception = $exception;
            }
        } else {
            return $this->return = $target->{$this->method}(...$this->args);
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

        if (($advisors = $this->advisors[Advisor::ADVICE_AROUND] ?? null) !== null) {
            foreach ($advisors as $advisor) {
                $advisor->advise($this);
            }

            if (!$this->invoked) {
                throw new MissingCallProcessException($this->method);
            }
        } else {
            $this->proceed();
        }

        if (($advisors = $this->advisors[Advisor::ADVICE_AFTER] ?? null) !== null) {
            foreach ($advisors as $advisor) {
                $advisor->advise($this);
            }
        }

        if ($this->exception === null) {
            if (($advisors = $this->advisors[Advisor::ADVICE_AFTER_RETURNING] ?? null) !== null) {
                foreach ($advisors as $advisor) {
                    $advisor->advise($this);
                }
            }
            return $this->return;
        } else {
            if (($advisors = $this->advisors[Advisor::ADVICE_AFTER_THROWING] ?? null) !== null) {
                foreach ($advisors as $advisor) {
                    $advisor->advise($this);
                }
            }

            throw $this->exception;
        }
    }

    /**
     * @return object
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return array
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * @return mixed
     */
    public function getReturn()
    {
        return $this->return;
    }

    /**
     * @param mixed $return
     */
    public function setReturn($return)
    {
        $this->return = $return;
    }

    /**
     * @return \Throwable
     */
    public function getException()
    {
        return $this->exception;
    }
}