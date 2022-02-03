<?php
declare(strict_types=1);

namespace ManaPHP\Http;

interface UrlInterface
{
    public function get(string|array $args, bool|string $scheme = false): string;
}