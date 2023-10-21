<?php
declare(strict_types=1);

namespace ManaPHP\Http;

interface AccessLogInterface
{
    public function log(): void;
}