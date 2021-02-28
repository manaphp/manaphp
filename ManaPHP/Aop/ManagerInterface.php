<?php

namespace ManaPHP\Aop;

interface ManagerInterface
{
    /**
     *
     * $this->before('App\Controllers\*::*Action', $func);
     *
     * @param string|array $pointcut
     * @param callable     $advice
     * @param int          $order
     *
     * @return static
     */
    public function before($pointcut, $advice, $order = 0);

    /**
     * @param string|array $pointcut
     * @param callable     $advice
     * @param int          $order
     *
     * @return static
     */
    public function after($pointcut, $advice, $order = 0);

    /**
     * @param string|array $pointcut
     * @param callable     $advice
     * @param int          $order
     *
     * @return static
     */
    public function afterReturning($pointcut, $advice, $order = 0);

    /**
     * @param string|array $pointcut
     * @param callable     $advice
     * @param int          $order
     *
     * @return static
     */
    public function afterThrowing($pointcut, $advice, $order = 0);

    /**
     * @param string|array $pointcut
     * @param callable     $advice
     * @param int          $order
     *
     * @return static
     */
    public function around($pointcut, $advice, $order = 0);
}