<?php
declare(strict_types=1);

namespace ManaPHP\Rpc;

interface ServerInterface
{
    public function start(): void;

    public function send(): void;
}