<?php

declare(strict_types=1);

namespace ManaPHP;

use JsonSerializable;
use ManaPHP\Alias\AliasNotExistException;
use ManaPHP\Alias\InvalidAliasNameException;
use ManaPHP\Di\Attribute\Autowired;
use function rtrim;
use function str_starts_with;
use function strpos;
use function strtr;
use function substr;

/**
 * Path alias manager implementation.
 *
 * @inheritDoc
 * @see https://github.com/manaphp/framework/blob/master/docs/en/reference/alias.md For complete API reference.
 */
class Alias implements AliasInterface, JsonSerializable
{
    #[Autowired] protected array $aliases = ['@manaphp' => __DIR__];

    /**
     * @inheritDoc
     */
    public function all(): array
    {
        return $this->aliases;
    }

    /**
     * Validates that the alias name starts with '@'.
     *
     * @param string $name The alias name to validate.
     * @throws InvalidAliasNameException If the alias name doesn't start with '@'.
     */
    protected function validateName(string $name): void
    {
        if (!str_starts_with($name, '@')) {
            throw new InvalidAliasNameException($name);
        }
    }

    /**
     * @inheritDoc
     */
    public function set(string $name, string $path): string
    {
        $this->validateName($name);

        if ($path === '') {
            $this->aliases[$name] = $path;
        } elseif (!str_starts_with($path, '@')) {
            if (DIRECTORY_SEPARATOR === '/' || str_starts_with($name, '@ns.')) {
                $this->aliases[$name] = rtrim($path, '/');
            } else {
                $this->aliases[$name] = rtrim(strtr($path, '\\', '/'), '/');
            }
        } else {
            $this->aliases[$name] = $this->resolve($path);
        }

        return $this->aliases[$name];
    }

    /**
     * @inheritDoc
     */
    public function get(string $name): ?string
    {
        $this->validateName($name);

        return $this->aliases[$name] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function has(string $name): bool
    {
        $this->validateName($name);

        return isset($this->aliases[$name]);
    }

    /**
     * @inheritDoc
     */
    public function resolve(string $path, array $context = []): string
    {
        if ($context !== []) {
            $replacements = [];
            foreach ($context as $k => $v) {
                $replacements["{{$k}}"] = (string)$v;
            }

            $path = strtr($path, $replacements);
        }

        if (!str_starts_with($path, '@')) {
            return DIRECTORY_SEPARATOR === '/' ? $path : strtr($path, '\\', '/');
        }

        if (DIRECTORY_SEPARATOR === '\\') {
            $path = strtr($path, '\\', '/');
        }

        if (($pos = strpos($path, '/')) === false) {
            if (!isset($this->aliases[$path])) {
                throw new AliasNotExistException('The alias "{path}" does not exist.', ['path' => $path]);
            }
            return $this->aliases[$path];
        }

        $alias = substr($path, 0, $pos);

        if (!isset($this->aliases[$alias])) {
            throw new AliasNotExistException('The alias "{alias}" does not exist for path "{path}".', ['alias' => $alias, 'path' => $path]);
        }

        return $this->aliases[$alias] . substr($path, $pos);
    }

    /**
     * @inheritDoc
     */
    public function remove(string $name): void
    {
        $this->validateName($name);

        unset($this->aliases[$name]);
    }

    /**
     * Returns all aliases for JSON serialization.
     *
     * @return array<string, string> All registered aliases.
     */
    public function jsonSerialize(): array
    {
        return $this->all();
    }
}
