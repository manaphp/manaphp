<?php
declare(strict_types=1);

namespace ManaPHP\Http;

interface ServerInterface
{
    public function start(): void;

    public function send(): void;
}