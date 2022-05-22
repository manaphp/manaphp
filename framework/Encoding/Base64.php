<?php
declare(strict_types=1);

namespace ManaPHP\Encoding;

class Base64 implements Base64Interface
{
    public function encode(string $str): string
    {
        return base64_encode($str);
    }

    public function decode(string $str): ?string
    {
        $v = base64_decode($str);

        return $v === false ? null : $v;
    }
}