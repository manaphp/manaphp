<?php
declare(strict_types=1);

namespace ManaPHP\Eventing;

interface TracerInterface
{
    public function start(): void;
}