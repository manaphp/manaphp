<?php

namespace ManaPHP\Aop;

interface JoinPointInterface
{
    /**
     * @return mixed|\Throwable
     */
    public function proceed();

    /**
     * @param array $args
     *
     * @return mixed
     */
    public function invoke($args);

    /**
     * @return object
     */
    public function getTarget();

    /**
     * @return string
     */
    public function getMethod();

    /**
     * @return array
     */
    public function getArgs();

    /**
     * @return mixed
     */
    public function getReturn();

    /**
     * @param mixed $return
     */
    public function setReturn($return);

    /**
     * @return \Exception
     */
    public function getException();
}
