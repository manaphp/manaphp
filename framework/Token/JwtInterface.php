<?php
declare(strict_types=1);

namespace ManaPHP\Token;

interface JwtInterface
{
    public function encode(array $claims, int $ttl, ?string $key = null): string;

    public function decode(string $token, bool $verify = true, ?string $key = null): array;

    public function verify(string $token, ?string $key = null): void;
}