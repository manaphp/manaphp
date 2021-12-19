<?php
declare(strict_types=1);

namespace ManaPHP\Mvc\View;

interface FlashInterface
{
    public function error(string $message): void;

    public function notice(string $message): void;

    public function success(string $message): void;

    public function warning(string $message): void;

    public function output(bool $remove = true): void;
}