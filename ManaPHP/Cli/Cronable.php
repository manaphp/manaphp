<?php

namespace ManaPHP\Cli;

interface Cronable
{
    /**
     * @return string|int|float
     */
    public function schedule();

    public function defaultCommand();
}