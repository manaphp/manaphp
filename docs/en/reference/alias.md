# Alias API Reference

Complete API reference for the `ManaPHP\Alias` class.

## Concepts {#concepts}

### What are Path Aliases? {#what-are-path-aliases}

Path aliases are string tokens that start with `@` and represent file or directory paths. They provide a convenient way to:

- Use short, memorable names instead of long absolute paths
- Build path hierarchies using alias references
- Support cross-platform path handling (Windows/Unix)
- Enable dynamic path resolution with placeholders

### Alias Naming {#alias-naming}

- **Must start with `@`**: All alias names must begin with the `@` character
- **Case-sensitive**: Alias names are case-sensitive
- **Valid characters**: After `@`, you can use letters, numbers, dots, and underscores
- **Special prefix**: Aliases starting with `@ns.` preserve backslashes (useful for namespace paths)

**Examples**:
- Valid: `@root`, `@app`, `@config`, `@ns.app`
- Invalid: `root` (missing `@`), `@ root` (space not recommended)

### Path Types {#path-types}

When setting an alias, you can use different types of paths:

1. **Absolute paths**: Full file system paths (e.g., `/var/www/myapp`)
2. **Relative paths**: Paths relative to current directory (e.g., `app`, `../config`)
3. **Alias references**: References to other aliases (e.g., `@root/app`)

**Note**: When using an alias reference, it gets resolved once during `set()` and the final resolved path is stored.

### Key Features {#key-features}

- **Path normalization**: Trailing slashes are automatically removed
- **Windows support**: Backslashes are converted to forward slashes (except for `@ns.` prefixed aliases)
- **Placeholder support**: Use `{key}` placeholders in `resolve()` calls with context values
- **One-time resolution**: Alias references are resolved during `set()`, not during `resolve()`

### Default Aliases {#default-aliases}

The Alias class comes with a default alias:

- `@manaphp` - Points to the framework directory (`__DIR__` of the Alias class)

---

## Class: `ManaPHP\Alias` {#class}

The Alias class provides path alias management functionality.

**Implements**: `ManaPHP\AliasInterface`, `JsonSerializable`

### Properties {#properties}

#### `protected array $aliases` {#aliases-property}

Stores all registered aliases. Default value includes `@manaphp` pointing to the framework directory.

---

## Methods {#methods}

### `all(): array` {#method-all}

Returns all registered aliases as an associative array.

**Returns**: `array` - Associative array where keys are alias names and values are their resolved paths.

**Example**:
```php
$aliases = $alias->all();
// Returns: ['@manaphp' => '/path/to/framework', '@root' => '/var/www', ...]
```

---

### `set(string $name, string $path): string` {#method-set}

Sets an alias to a path value.

**Parameters**:
- `$name` (string): The alias name, must start with `@`
- `$path` (string): The path value. Can be:
  - An absolute or relative file system path
  - An empty string
  - Another alias reference (e.g., `@root/app`)

**Returns**: `string` - The resolved/stored path value.

**Throws**:
- `ManaPHP\Alias\InvalidAliasNameException` - If the alias name doesn't start with `@`

**Behavior**:
- Empty paths are stored as-is
- Non-alias paths (not starting with `@`) are normalized:
  - Trailing slashes are removed
  - On Windows, backslashes are converted to forward slashes (except for `@ns.` prefixed aliases)
- Alias references (starting with `@`) are resolved once during `set()` and the resolved value is stored
- For `@ns.` prefixed aliases, backslashes are preserved (useful for namespace paths)

**Example**:
```php
// Absolute path
$result = $alias->set('@root', '/var/www/myapp');
// Returns: '/var/www/myapp'

// Relative path
$result = $alias->set('@app', 'app');
// Returns: 'app'

// Alias reference
$alias->set('@root', '/var/www');
$result = $alias->set('@app', '@root/app');
// Returns: '/var/www/app' (resolved and stored)

// Empty path
$result = $alias->set('@empty', '');
// Returns: ''

// Namespace alias
$result = $alias->set('@ns.app', 'App\\Controllers');
// Returns: 'App\\Controllers' (backslashes preserved)
```

**Note**: The `set()` method does not support placeholders in the path parameter. If a placeholder is included, it will be stored as-is and won't be processed during `resolve()`.

---

### `get(string $name): ?string` {#method-get}

Gets the value of an alias.

**Parameters**:
- `$name` (string): The alias name, must start with `@`

**Returns**: `?string` - The alias value, or `null` if the alias doesn't exist.

**Throws**:
- `ManaPHP\Alias\InvalidAliasNameException` - If the alias name doesn't start with `@`

**Example**:
```php
$alias->set('@root', '/var/www');
$path = $alias->get('@root');
// Returns: '/var/www'

$path = $alias->get('@nonexistent');
// Returns: null
```

---

### `has(string $name): bool` {#method-has}

Checks if an alias exists.

**Parameters**:
- `$name` (string): The alias name, must start with `@`

**Returns**: `bool` - `true` if the alias exists, `false` otherwise.

**Throws**:
- `ManaPHP\Alias\InvalidAliasNameException` - If the alias name doesn't start with `@`

**Example**:
```php
$alias->set('@root', '/var/www');
$exists = $alias->has('@root');
// Returns: true

$exists = $alias->has('@nonexistent');
// Returns: false
```

---

### `resolve(string $path, array $context = []): string` {#method-resolve}

Resolves a path, which may contain an alias, to an actual file system path.

**Parameters**:
- `$path` (string): The path to resolve. Can be:
  - An alias path (e.g., `@root/app/controllers`)
  - A non-alias absolute or relative path
- `$context` (array, optional): Associative array for placeholder replacement. Keys should not contain `{}` characters.

**Returns**: `string` - The resolved path.

**Throws**:
- `ManaPHP\Alias\AliasNotExistException` - If the alias in the path doesn't exist

**Behavior**:
1. **Placeholder replacement**: If `$context` is provided and not empty, placeholders in the format `{key}` are replaced with corresponding context values before alias resolution
2. **Non-alias paths**: If the path doesn't start with `@`, it's returned as-is (with backslash conversion on Windows)
3. **Alias resolution**: 
   - If the path is just an alias name (e.g., `@root`), returns the alias value
   - If the path contains a sub-path (e.g., `@root/app`), concatenates the alias value with the sub-path
4. **Windows support**: On Windows, backslashes in alias paths are converted to forward slashes

**Example**:
```php
$alias->set('@root', '/var/www');

// Simple alias
$path = $alias->resolve('@root');
// Returns: '/var/www'

// Alias with sub-path
$path = $alias->resolve('@root/app/controllers');
// Returns: '/var/www/app/controllers'

// Non-alias path
$path = $alias->resolve('/absolute/path');
// Returns: '/absolute/path'

// With placeholder
$path = $alias->resolve('@root/{app_id}/log.txt', ['app_id' => 'myapp']);
// Returns: '/var/www/myapp/log.txt'

// Multiple placeholders
$path = $alias->resolve('@root/{dir}/{file}.log', [
    'dir' => 'logs',
    'file' => 'app'
]);
// Returns: '/var/www/logs/app.log'

// Missing context key (placeholder preserved)
$path = $alias->resolve('@root/{app_id}/log.txt', ['other_key' => 'value']);
// Returns: '/var/www/{app_id}/log.txt'
```

**Limitations**:
- **Recursive resolution**: The `resolve()` method does not recursively resolve stored alias values. If an alias value is another alias reference, it won't be resolved again
- **Placeholder in stored values**: Placeholders in stored alias values (set via `set()`) are not processed during `resolve()`
- **Context key format**: Context keys should not contain `{}` characters, as they won't match placeholders correctly

---

### `remove(string $name): void` {#method-remove}

Removes an alias.

**Parameters**:
- `$name` (string): The alias name, must start with `@`

**Throws**:
- `ManaPHP\Alias\InvalidAliasNameException` - If the alias name doesn't start with `@`

**Example**:
```php
$alias->set('@temp', '/tmp');
$alias->remove('@temp');
$alias->has('@temp'); // Returns: false
```

---

### `jsonSerialize(): array` {#method-json-serialize}

Returns all aliases for JSON serialization.

**Returns**: `array` - All registered aliases as an associative array.

**Note**: This method is called automatically when using `json_encode()` on an Alias instance.

**Example**:
```php
$alias->set('@root', '/var/www');
$alias->set('@app', '/var/www/app');

$json = json_encode($alias);
// Returns: '{"@root":"/var/www","@app":"/var/www/app",...}'
```

---

## Interface: `ManaPHP\AliasInterface` {#interface}

The interface that defines the contract for alias management.

### Methods

- `all(): array`
- `set(string $name, string $path): string`
- `get(string $name): ?string`
- `has(string $name): bool`
- `resolve(string $path, array $context = []): string`
- `remove(string $name): void`

---

## Platform-Specific Behavior {#platform-specific-behavior}

### Windows {#windows}

- Backslashes in paths are converted to forward slashes (except for `@ns.` prefixed aliases)
- Paths starting with drive letters (e.g., `C:\`) are handled correctly

### Unix/Linux {#unix-linux}

- Paths are handled as-is
- No backslash conversion needed

---

## Notes {#notes}

1. **One-time resolution**: When setting an alias with an alias reference (e.g., `set('@app', '@root/app')`), the reference is resolved once during `set()`. The stored value is the resolved path, not the reference itself.

2. **No recursive resolution**: The `resolve()` method does not recursively resolve stored alias values. If you need nested aliases, resolve them during `set()`.

3. **Placeholder limitations**: 
   - Placeholders are only processed in the input path to `resolve()`, not in stored alias values
   - Context keys should not contain `{}` characters

4. **Trailing slashes**: Trailing slashes are automatically removed from paths during `set()`.

