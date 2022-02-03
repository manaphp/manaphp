<?php
declare(strict_types=1);

namespace ManaPHP\Event;

interface ListenInterface
{
    public function listen(): void;
}