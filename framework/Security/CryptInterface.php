<?php
declare(strict_types=1);

namespace ManaPHP\Security;

interface CryptInterface
{
    public function encrypt(string $text, string $key): string;

    public function decrypt(string $text, string $key): string;

    public function getDerivedKey(string $type): string;
}