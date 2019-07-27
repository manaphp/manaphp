<?php
namespace ManaPHP;

interface CronInterface
{
    /**
     * @return string|int|float
     */
    public function schedule();

    /**
     * @return void
     */
    public function run();
}