<?php

declare(strict_types=1);

namespace ManaPHP\Viewing;

use ManaPHP\Alias\Path;
use function is_file;
use function md5_file;
use function rtrim;
use function str_contains;
use function substr;

class Asset implements AssetInterface
{
    protected array $urls = [];

    public function get(string $path): string
    {
        if (($url = $this->urls[$path] ?? null) === null) {
            if (str_contains($path, '?')) {
                $url = Path::resolve('@asset') . rtrim($path, '?');
            } elseif (is_file($file = Path::of('@public') . $path)) {
                $url = Path::resolve('@asset') . '?v=' . substr(md5_file($file), 0, 12);
            } else {
                $url = Path::resolve('@asset') . $path;
            }

            $this->urls[$path] = $url;
        }

        return $url;
    }
}
