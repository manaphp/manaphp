<?php
declare(strict_types=1);

namespace ManaPHP;

use JsonSerializable;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Exception\MisuseException;

class Alias implements AliasInterface, JsonSerializable
{
    #[Autowired] protected array $aliases = ['@manaphp' => __DIR__];

    public function all(): array
    {
        return $this->aliases;
    }

    public function set(string $name, string $path): string
    {
        if ($name[0] !== '@') {
            throw new MisuseException(['`{name}` must start with `@`', 'name' => $name]);
        }

        if ($path === '') {
            $this->aliases[$name] = $path;
        } elseif ($path[0] !== '@') {
            if (DIRECTORY_SEPARATOR === '/' || str_starts_with($name, '@ns.')) {
                $this->aliases[$name] = $path;
            } else {
                $this->aliases[$name] = strtr($path, '\\', '/');
            }
        } else {
            $this->aliases[$name] = $this->resolve($path);
        }

        return $this->aliases[$name];
    }

    public function get(string $name): ?string
    {
        if ($name[0] !== '@') {
            throw new MisuseException(['`{name}` must start with `@`', 'name' => $name]);
        }

        return $this->aliases[$name] ?? null;
    }

    public function has(string $name): bool
    {
        if ($name[0] !== '@') {
            throw new MisuseException(['`{name}` must start with `@`', 'name' => $name]);
        }

        return isset($this->aliases[$name]);
    }

    public function resolve(string $path): string
    {
        if ($path[0] !== '@') {
            return DIRECTORY_SEPARATOR === '/' ? $path : strtr($path, '\\', '/');
        }

        if (str_contains($path, '{') && preg_match_all('#{([^}]+)}#', $path, $matches)) {
            foreach ((array)$matches[1] as $k => $match) {
                if (is_numeric($match)) {
                    $replaced = substr(bin2hex(random_bytes($match / 2 + 1)), 0, (int)$match);
                } else {
                    $ts = $ts ?? time();
                    $replaced = date($match, $ts);
                }

                $path = str_replace($matches[0][$k], $replaced, $path);
            }
        }

        if (DIRECTORY_SEPARATOR === '\\') {
            $path = strtr($path, '\\', '/');
        }

        if (($pos = strpos($path, '/')) === false) {
            if (!isset($this->aliases[$path])) {
                throw new InvalidArgumentException(['`{alias}` is not exists', 'alias' => $path]);
            }
            return $this->aliases[$path];
        }

        $alias = substr($path, 0, $pos);

        if (!isset($this->aliases[$alias])) {
            throw new InvalidArgumentException(['`{1}` is not exists for `{2}`', $alias, $path]);
        }

        return $this->aliases[$alias] . substr($path, $pos);
    }

    public function jsonSerialize(): array
    {
        return $this->all();
    }
}