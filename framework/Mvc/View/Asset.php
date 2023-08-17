<?php
declare(strict_types=1);

namespace ManaPHP\Mvc\View;

use ManaPHP\AliasInterface;
use ManaPHP\Di\Attribute\Inject;

class Asset implements AssetInterface
{
    #[Inject] protected AliasInterface $alias;

    protected array $urls = [];

    public function get(string $path): string
    {
        if (($url = $this->urls[$path] ?? null) === null) {
            if (str_contains($path, '?')) {
                $url = $this->alias->get('@asset') . rtrim($path, '?');
            } elseif (is_file($file = $this->alias->get('@public') . $path)) {
                $url = $this->alias->get('@asset') . '?v=' . substr(md5_file($file), 0, 12);
            } else {
                $url = $this->alias->get('@asset') . $path;
            }

            $this->urls[$path] = $url;
        }

        return $url;
    }
}