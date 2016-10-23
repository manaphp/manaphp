<?php
namespace ManaPHP\Meter;

/**
 * Interface ManaPHP\Meter\RoundInterface
 *
 * @package ManaPHP\Meter
 */
interface RoundInterface
{
    /**
     * @param string $type
     * @param string $id
     * @param int    $duration
     *
     * @return static
     */
    public function record($type, $id, $duration);

    /**
     * @param string $type
     * @param string $id
     *
     * @return void
     */
    public function flush($type, $id = null);
}