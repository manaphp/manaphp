<?php
namespace ManaPHP;

interface RoundMeterInterface
{
    /**
     * @param string $type
     * @param string $id
     * @param int    $duration
     *
     * @return static
     */
    public function increment($type, $id, $duration);
}