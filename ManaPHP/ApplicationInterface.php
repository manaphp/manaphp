<?php

namespace ManaPHP;

interface ApplicationInterface
{
    /**
     * @return void
     */
    public function main();

    /**
     * @return void
     */
    public function cli();
}