<?php
declare(strict_types=1);

namespace ManaPHP\Debugging;

interface DataDumpInterface
{
    public function output(mixed $message): void;
}