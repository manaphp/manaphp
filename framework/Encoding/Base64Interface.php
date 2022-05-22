<?php
declare(strict_types=1);

namespace ManaPHP\Encoding;

interface Base64Interface
{
    public function encode(string $str): string;

    public function decode(string $str): ?string;
}