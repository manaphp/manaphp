<?php
namespace ManaPHP;

interface StopwatchInterface
{
    /**
     * @param string $name
     *
     * @return static
     */
    public function start($name);

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isStarted($name);

    /**
     * @param string $name
     *
     * @return static
     */
    public function stop($name);

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isStopped($name);

    /**
     * @param string|array $name
     *
     * @return float
     * @throws \ManaPHP\Stopwatch\Exception
     */
    public function getElapsedTime($name);

    /**
     * @param string $name
     *
     * @return int
     * @throws \ManaPHP\Stopwatch\Exception
     */
    public function getUsedMemory($name);

    /**
     * @param int|int[]           $times
     * @param callable|callable[] $functions
     *
     * @return array
     */
    public function test($times, $functions);
}