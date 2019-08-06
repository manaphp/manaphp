<?php

namespace ManaPHP\Cli;

interface HandlerInterface
{
    /**
     * @param array $args
     *
     * @return int
     * @throws \ManaPHP\Exception\AbortException
     * @throws \ManaPHP\Cli\Request\Exception
     */
    public function handle($args = null);

    /**
     * @return array
     */
    public function getArgs();
}