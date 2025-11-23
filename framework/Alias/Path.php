<?php
declare(strict_types=1);

namespace ManaPHP\Alias;

use ManaPHP\AliasInterface;
use ManaPHP\Helper\Container;
use Stringable;
use function is_string;
use function str_starts_with;

class Path implements Stringable
{
    protected string $path;

    public function __construct(string $path)
    {
        if (str_starts_with($path, '@')) {
            $this->path = Container::get(AliasInterface::class)->resolve($path);
        } else {
            $this->path = $path;
        }
    }

    public function __toString(): string
    {
        return $this->path;
    }

    public static function of(string|Path $path): static
    {
        return is_string($path) ? new static($path) : $path;
    }

    public static function resolve(string|Path $path): string
    {
        return (string)self::of($path);
    }

    public static function basename(string|Path $path): string
    {
        return basename(self::resolve($path));
    }

    public static function dirname(string|Path $path): string
    {
        return dirname(self::resolve($path));
    }

    public static function extension(string|Path $path): string
    {
        return pathinfo(self::resolve($path), PATHINFO_EXTENSION);
    }

    public static function filename(string|Path $path): string
    {
        return pathinfo(self::resolve($path), PATHINFO_FILENAME);
    }
}