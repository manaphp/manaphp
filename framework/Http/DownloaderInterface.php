<?php
declare(strict_types=1);

namespace ManaPHP\Http;

interface DownloaderInterface
{
    public function download(array $files, mixed $options = []): array;
}