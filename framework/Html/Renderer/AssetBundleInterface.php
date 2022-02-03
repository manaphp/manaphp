<?php
declare(strict_types=1);

namespace ManaPHP\Html\Renderer;

interface AssetBundleInterface
{
    public function bundle(array $files, string $name = 'app'): string;
}