<?php
declare(strict_types=1);

namespace ManaPHP\Http;

interface DownloaderInterface
{
    public function download(string|array $files, mixed $options = []): false|string|array;
}