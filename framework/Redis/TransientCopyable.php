<?php
declare(strict_types=1);

namespace ManaPHP\Redis;

interface TransientCopyable
{
    public function getTransientCopy(): static;
}