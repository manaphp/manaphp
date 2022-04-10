<?php
declare(strict_types=1);

namespace ManaPHP\Mvc\View;

interface AssetInterface
{
    public function get(string $path): string;
}