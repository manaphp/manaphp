<?php
declare(strict_types=1);

namespace ManaPHP\Encoding;

class Base64Url implements Base64UrlInterface
{
    public function encode(string $str): string
    {
        return strtr(rtrim(base64_encode($str), '='), '+/', '-_');
    }

    public function decode(string $str): ?string
    {
        $v = base64_decode(strtr($str, '-_', '+/'));
        return $v === false ? null : $v;
    }
}