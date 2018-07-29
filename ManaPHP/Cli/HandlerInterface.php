<?php

namespace ManaPHP\Cli;

interface HandlerInterface
{
    /**
     * @param array $args
     *
     * @return int
     */
    public function handle($args = null);

    /**
     * @return array
     */
    public function getArgs();
}