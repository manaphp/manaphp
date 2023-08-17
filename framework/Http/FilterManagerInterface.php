<?php
declare(strict_types=1);

namespace ManaPHP\Http;

interface FilterManagerInterface
{
    public function register(): void;
}