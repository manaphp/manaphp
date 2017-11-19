<?php
namespace ManaPHP\Counter;

/**
 * Interface ManaPHP\Counter\EngineInterface
 *
 * @package counter
 */
interface EngineInterface
{
    /**
     * @param string $key
     *
     * @return int
     */
    public function get($key);

    /**
     * @param string $key
     * @param int    $step
     *
     * @return int
     */
    public function increment($key, $step = 1);

    /**
     * @param string $key
     *
     * @return void
     */
    public function delete($key);
}