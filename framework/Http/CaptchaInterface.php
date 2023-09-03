<?php
declare(strict_types=1);

namespace ManaPHP\Http;

interface CaptchaInterface
{
    public function draw(string $code, int $width, int $height): string;

    public function generate(int $width = 100, int $height = 30, int $ttl = 300): ResponseInterface;

    public function verify(string $code, bool $isTry = false): void;
}