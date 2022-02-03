<?php
declare(strict_types=1);

namespace ManaPHP\Debugging;

interface XdebugTracerInterface
{
    public function start(): void;
}