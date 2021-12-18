<?php
declare(strict_types=1);

namespace ManaPHP\Http;

interface CaptchaInterface
{
    public function generate(int $width = 100, int $height = 30, int $ttl = 300): ResponseInterface;

    public function verify(?string $code = null, bool $isTry = false): void;
}