<?php

namespace ManaPHP\Tracer;

interface ManagerInterface
{
    /**
     * @return array
     */
    public function getTracers();

    public function listen();
}