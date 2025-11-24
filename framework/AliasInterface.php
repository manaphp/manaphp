<?php

declare(strict_types=1);

namespace ManaPHP;

use ManaPHP\Alias\AliasNotExistException;
use ManaPHP\Alias\InvalidAliasNameException;

/**
 * Interface for path alias management.
 *
 * Provides methods to define, retrieve, and resolve path aliases.
 * Path aliases are string tokens that start with `@` and represent file or directory paths.
 *
 * @example
 * $alias = new \ManaPHP\Alias();
 * $alias->set('@root', '/var/www/myapp');
 * $alias->set('@app', '@root/app');
 * $path = $alias->resolve('@app/controllers');
 * // Returns: '/var/www/myapp/app/controllers'
 *
 * @see \ManaPHP\Alias For the implementation of this interface.
 * @see https://github.com/manaphp/framework/blob/master/docs/en/reference/alias.md For complete API reference.
 */
interface AliasInterface
{
    /**
     * Returns all registered aliases as an associative array.
     *
     * @return array<string, string> Associative array where keys are alias names and values are their resolved paths.
     */
    public function all(): array;

    /**
     * Sets an alias to a path value.
     *
     * @param string $name The alias name, must start with `@`.
     * @param string $path The path value. Can be:
     *                     - An absolute or relative file system path
     *                     - An empty string
     *                     - Another alias reference (e.g., `@root/app`)
     * @return string The resolved/stored path value.
     * @throws InvalidAliasNameException If the alias name doesn't start with `@`.
     *
     * Behavior:
     * - If the path is an alias reference (starts with '@'), it will be resolved immediately and the resolved path will be stored.
     * - On Windows, backslashes in non-alias paths are converted to forward slashes, except for '@ns.' prefixed aliases.
     * - Trailing slashes are removed from stored paths.
     *
     * @example
     * $alias->set('@root', '/var/www/myapp');
     * $alias->set('@app', '@root/app'); // Resolves to '/var/www/myapp/app'
     * $alias->set('@config', '@app/config'); // Resolves to '/var/www/myapp/app/config'
     */
    public function set(string $name, string $path): string;

    /**
     * Gets the value of an alias.
     *
     * @param string $name The alias name, must start with `@`.
     * @return string|null The alias value, or `null` if the alias doesn't exist.
     * @throws InvalidAliasNameException If the alias name doesn't start with `@`.
     */
    public function get(string $name): ?string;

    /**
     * Checks if an alias exists.
     *
     * @param string $name The alias name, must start with `@`.
     * @return bool `true` if the alias exists, `false` otherwise.
     * @throws InvalidAliasNameException If the alias name doesn't start with `@`.
     */
    public function has(string $name): bool;

    /**
     * Resolves a path, which may contain an alias, to an actual file system path.
     *
     * @param string $path The path to resolve. Can be:
     *                     - An alias path (e.g., `@root/app/controllers`)
     *                     - A non-alias absolute or relative path
     * @param array<string, string|int|float> $context Optional associative array for placeholder replacement.
     *                                                  Keys should not contain `{}` characters.
     *                                                  Values will be converted to strings.
     * @return string The resolved path.
     * @throws AliasNotExistException If the alias in the path doesn't exist.
     *
     * Behavior:
     * - Placeholder replacement (from context) is performed first, before alias resolution.
     * - On Windows, backslashes in the path are converted to forward slashes before processing.
     * - Alias resolution is performed once; stored alias values are not recursively resolved.
     *
     * @example
     * $alias->set('@root', '/var/www/myapp');
     * $alias->resolve('@root/app/controllers');
     * // Returns: '/var/www/myapp/app/controllers'
     *
     * @example
     * $alias->resolve('@root/{app_id}/log.txt', ['app_id' => 'myapp']);
     * // Returns: '/var/www/myapp/myapp/log.txt'
     */
    public function resolve(string $path, array $context = []): string;

    /**
     * Removes an alias.
     *
     * @param string $name The alias name, must start with `@`.
     * @throws InvalidAliasNameException If the alias name doesn't start with `@`.
     */
    public function remove(string $name): void;
}
