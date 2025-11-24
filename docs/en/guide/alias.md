# Alias Guide

The Alias component provides a convenient way to manage path aliases in your ManaPHP application. It allows you to define short, memorable names for long file system paths and resolve them when needed.

## Overview {#overview}

Path aliases are string tokens that start with `@` and represent file or directory paths. They are useful for:

- Making paths more readable and maintainable
- Supporting cross-platform path handling (Windows/Unix)
- Enabling dynamic path resolution with placeholders
- Simplifying path management in configuration files

## Basic Usage {#basic-usage}

### Setting Aliases {#setting-aliases}

In most cases, you configure aliases in your application's configuration file. The Kernel will automatically register them when the application starts.

#### Configuration File {#configuration-file}

Configure aliases in `config/app.php`:

```php
<?php
declare(strict_types=1);

return ['ManaPHP\Di\ConfigInterface' => [
    'config' => [
        'aliases' => [
            '@root'    => __DIR__,
            '@app'     => '@root/app',
            '@config'  => '@root/config',
            '@runtime' => '@root/runtime',
            '@public'  => '@root/public',
            '@views'   => '@app/views',
        ],
    ],
]];
```

The Kernel automatically registers these aliases during application startup. You can use absolute paths, relative paths, or reference other aliases (like `@root/app`).

#### Programmatic Setting {#programmatic-setting}

You can also set aliases programmatically using the `set()` method:

```php
use ManaPHP\Alias;

$alias = new Alias();

// Set an alias with an absolute path
$alias->set('@root', '/var/www/myapp');

// Set an alias with a relative path
$alias->set('@app', 'app');

// Set an alias that references another alias
$alias->set('@views', '@root/app/views');
```

**Note**: Alias names must start with `@`. When you set an alias with a reference to another alias (like `@root/app`), it gets resolved immediately and the final path is stored.

### Getting Alias Values {#getting-alias-values}

Retrieve the value of an alias using the `get()` method:

```php
$rootPath = $alias->get('@root'); // Returns: '/var/www/myapp'
$appPath = $alias->get('@app');   // Returns: 'app'
```

### Checking Alias Existence {#checking-alias-existence}

Check if an alias exists using the `has()` method:

```php
if ($alias->has('@root')) {
    // Alias exists
}
```

### Resolving Paths {#resolving-paths}

The `resolve()` method converts an alias path to its actual file system path:

```php
// Resolve a simple alias
$path = $alias->resolve('@root');
// Returns: '/var/www/myapp'

// Resolve an alias with a sub-path
$path = $alias->resolve('@root/app/controllers');
// Returns: '/var/www/myapp/app/controllers'

// Resolve a non-alias path (returns as-is)
$path = $alias->resolve('/absolute/path');
// Returns: '/absolute/path'
```

### Using Placeholders {#using-placeholders}

You can use placeholders in paths that get replaced with context values:

```php
$alias->set('@root', '/var/www');

// Resolve with placeholder
$path = $alias->resolve('@root/{app_id}/log.txt', ['app_id' => 'myapp']);
// Returns: '/var/www/myapp/log.txt'

// Multiple placeholders
$path = $alias->resolve('@root/{dir}/{file}.log', [
    'dir' => 'logs',
    'file' => 'app'
]);
// Returns: '/var/www/logs/app.log'
```

**Tip**: Placeholders only work in `resolve()`, not in `set()`. Use simple context keys without `{}` characters.

## Common Patterns {#common-patterns}

### Application Structure {#application-structure}

A typical application structure using aliases:

```php
$alias->set('@root', __DIR__);
$alias->set('@app', '@root/app');
$alias->set('@config', '@root/config');
$alias->set('@runtime', '@root/runtime');
$alias->set('@public', '@root/public');
$alias->set('@views', '@app/views');
$alias->set('@controllers', '@app/controllers');
```

### Dynamic Log Paths {#dynamic-log-paths}

Using placeholders for dynamic paths:

```php
$alias->set('@runtime', '/var/www/runtime');

// Resolve with dynamic app ID
$logPath = $alias->resolve('@runtime/{app_id}/app.log', [
    'app_id' => 'myapp'
]);
// Returns: '/var/www/runtime/myapp/app.log'
```

### Removing Aliases {#removing-aliases}

Remove an alias when it's no longer needed:

```php
$alias->remove('@oldAlias');
```

### Getting All Aliases {#getting-all-aliases}

Retrieve all registered aliases:

```php
$allAliases = $alias->all();
// Returns: ['@root' => '/var/www', '@app' => '/var/www/app', ...]
```

## Best Practices {#best-practices}

1. **Use descriptive names**: Choose alias names that clearly indicate their purpose (e.g., `@root`, `@app`, `@config`)

2. **Organize hierarchically**: Use nested aliases to build a logical path structure (e.g., `@app` → `@views` → `@controllers`)

3. **Use placeholders in `resolve()` only**: Placeholders work in `resolve()` calls, not in `set()`

4. **Keep context keys simple**: Don't use `{}` characters in context keys

5. **Handle errors**: Always wrap alias operations in try-catch blocks when dealing with user input

## Error Handling {#error-handling}

### Invalid Alias Name {#invalid-alias-name}

If you try to set, get, or remove an alias with an invalid name (not starting with `@`), an `InvalidAliasNameException` is thrown:

```php
try {
    $alias->set('invalid', '/path');
} catch (ManaPHP\Alias\InvalidAliasNameException $e) {
    // Handle error
}
```

### Alias Not Found {#alias-not-found}

If you try to resolve a non-existent alias, an `AliasNotExistException` is thrown:

```php
try {
    $alias->resolve('@nonexistent');
} catch (ManaPHP\Alias\AliasNotExistException $e) {
    // Handle error
}
```

## Complete Example {#complete-example}

Here's a complete example showing how to use aliases in your application:

```php
use ManaPHP\Alias;

$alias = new Alias();

// Set up application structure
$alias->set('@root', __DIR__);
$alias->set('@app', '@root/app');
$alias->set('@config', '@root/config');
$alias->set('@runtime', '@root/runtime');

// Use aliases
$configPath = $alias->resolve('@config/app.php');
$logPath = $alias->resolve('@runtime/{app_id}/app.log', [
    'app_id' => 'myapp'
]);

// Check and get
if ($alias->has('@root')) {
    $root = $alias->get('@root');
    echo "Root directory: $root\n";
}
```

## Next Steps {#next-steps}

For detailed API documentation, including all method parameters, return types, exceptions, and technical details, see the [Alias API Reference](../reference/alias.md).
