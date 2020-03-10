<?php
namespace ManaPHP\Coroutine;

interface SchedulerInterface
{
    /**
     * @param callable $fn
     * @param mixed    ...$args
     *
     * @return static
     */
    public function add($fn, ...$args);

    /**
     * @return array
     */
    public function start();

    /**
     * @param callable  $fn
     * @param array|int $args
     * @param int       $count
     *
     * @return void
     */
    public function parallel($fn, $args, $count = null);
}
