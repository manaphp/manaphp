<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Adapter\Native;

interface SenderInterface
{
    public function send(): void;
}