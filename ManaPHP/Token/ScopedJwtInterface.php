<?php
declare(strict_types=1);

namespace ManaPHP\Token;

interface ScopedJwtInterface
{
    public function getKey(string $scope): string;

    public function encode(array $claims, int $ttl, string $scope): string;

    public function decode(string $token, string $scope, bool $verify = true): array;

    public function verify(string $token, string $scope): void;
}