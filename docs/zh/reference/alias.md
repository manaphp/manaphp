# Alias API 参考

`ManaPHP\Alias` 类的完整 API 参考。

## 概念 {#concepts}

### 什么是路径别名？ {#what-are-path-aliases}

路径别名是以 `@` 开头的字符串标记，代表文件或目录路径。它们提供了一种便捷的方式来：

- 使用简短、易记的名称代替长的绝对路径
- 使用别名引用构建路径层次结构
- 支持跨平台路径处理（Windows/Unix）
- 支持使用占位符进行动态路径解析

### 别名命名 {#alias-naming}

- **必须以 `@` 开头**：所有别名名称必须以 `@` 字符开头
- **区分大小写**：别名名称区分大小写
- **有效字符**：在 `@` 之后，您可以使用字母、数字、点和下划线
- **特殊前缀**：以 `@ns.` 开头的别名保留反斜杠（用于命名空间路径）

**示例**：
- 有效：`@root`、`@app`、`@config`、`@ns.app`
- 无效：`root`（缺少 `@`）、`@ root`（不推荐使用空格）

### 路径类型 {#path-types}

设置别名时，您可以使用不同类型的路径：

1. **绝对路径**：完整的文件系统路径（如 `/var/www/myapp`）
2. **相对路径**：相对于当前目录的路径（如 `app`、`../config`）
3. **别名引用**：对其他别名的引用（如 `@root/app`）

**注意**：使用别名引用时，它会在 `set()` 期间解析一次，并存储最终解析的路径。

### 主要特性 {#key-features}

- **路径规范化**：自动删除尾部斜杠
- **Windows 支持**：反斜杠转换为正斜杠（`@ns.` 前缀别名除外）
- **占位符支持**：在 `resolve()` 调用中使用 `{key}` 占位符和上下文值
- **一次性解析**：别名引用在 `set()` 期间解析，而不是在 `resolve()` 期间

### 默认别名 {#default-aliases}

Alias 类带有一个默认别名：

- `@manaphp` - 指向框架目录（Alias 类的 `__DIR__`）

---

## 类：`ManaPHP\Alias` {#class}

Alias 类提供路径别名管理功能。

**实现**：`ManaPHP\AliasInterface`、`JsonSerializable`

### 属性 {#properties}

#### `protected array $aliases` {#aliases-property}

存储所有已注册的别名。默认值包括指向框架目录的 `@manaphp`。

---

## 方法 {#methods}

### `all(): array` {#method-all}

返回所有已注册的别名作为关联数组。

**返回**：`array` - 关联数组，键为别名名称，值为其解析的路径。

**示例**：
```php
$aliases = $alias->all();
// 返回: ['@manaphp' => '/path/to/framework', '@root' => '/var/www', ...]
```

---

### `set(string $name, string $path): string` {#method-set}

将别名设置为路径值。

**参数**：
- `$name` (string)：别名名称，必须以 `@` 开头
- `$path` (string)：路径值。可以是：
  - 绝对或相对文件系统路径
  - 空字符串
  - 另一个别名引用（如 `@root/app`）

**返回**：`string` - 解析/存储的路径值。

**抛出**：
- `ManaPHP\Alias\InvalidAliasNameException` - 如果别名名称不以 `@` 开头

**行为**：
- 空路径按原样存储
- 非别名路径（不以 `@` 开头）会被规范化：
  - 删除尾部斜杠
  - 在 Windows 上，反斜杠转换为正斜杠（`@ns.` 前缀别名除外）
- 别名引用（以 `@` 开头）在 `set()` 期间解析一次，并存储解析的值
- 对于 `@ns.` 前缀别名，保留反斜杠（用于命名空间路径）

**示例**：
```php
// 绝对路径
$result = $alias->set('@root', '/var/www/myapp');
// 返回: '/var/www/myapp'

// 相对路径
$result = $alias->set('@app', 'app');
// 返回: 'app'

// 别名引用
$alias->set('@root', '/var/www');
$result = $alias->set('@app', '@root/app');
// 返回: '/var/www/app'（已解析并存储）

// 空路径
$result = $alias->set('@empty', '');
// 返回: ''

// 命名空间别名
$result = $alias->set('@ns.app', 'App\\Controllers');
// 返回: 'App\\Controllers'（保留反斜杠）
```

**注意**：`set()` 方法不支持路径参数中的占位符。如果包含占位符，它将按原样存储，在 `resolve()` 期间不会处理。

---

### `get(string $name): ?string` {#method-get}

获取别名的值。

**参数**：
- `$name` (string)：别名名称，必须以 `@` 开头

**返回**：`?string` - 别名值，如果别名不存在则返回 `null`。

**抛出**：
- `ManaPHP\Alias\InvalidAliasNameException` - 如果别名名称不以 `@` 开头

**示例**：
```php
$alias->set('@root', '/var/www');
$path = $alias->get('@root');
// 返回: '/var/www'

$path = $alias->get('@nonexistent');
// 返回: null
```

---

### `has(string $name): bool` {#method-has}

检查别名是否存在。

**参数**：
- `$name` (string)：别名名称，必须以 `@` 开头

**返回**：`bool` - 如果别名存在则返回 `true`，否则返回 `false`。

**抛出**：
- `ManaPHP\Alias\InvalidAliasNameException` - 如果别名名称不以 `@` 开头

**示例**：
```php
$alias->set('@root', '/var/www');
$exists = $alias->has('@root');
// 返回: true

$exists = $alias->has('@nonexistent');
// 返回: false
```

---

### `resolve(string $path, array $context = []): string` {#method-resolve}

将可能包含别名的路径解析为实际的文件系统路径。

**参数**：
- `$path` (string)：要解析的路径。可以是：
  - 别名路径（如 `@root/app/controllers`）
  - 非别名绝对或相对路径
- `$context` (array, 可选)：用于占位符替换的关联数组。键不应包含 `{}` 字符。

**返回**：`string` - 解析的路径。

**抛出**：
- `ManaPHP\Alias\AliasNotExistException` - 如果路径中的别名不存在

**行为**：
1. **占位符替换**：如果提供了 `$context` 且不为空，在别名解析之前，格式为 `{key}` 的占位符会被相应的上下文值替换
2. **非别名路径**：如果路径不以 `@` 开头，则按原样返回（在 Windows 上转换反斜杠）
3. **别名解析**：
   - 如果路径只是别名名称（如 `@root`），返回别名值
   - 如果路径包含子路径（如 `@root/app`），将别名值与子路径连接
4. **Windows 支持**：在 Windows 上，别名路径中的反斜杠会转换为正斜杠

**示例**：
```php
$alias->set('@root', '/var/www');

// 简单别名
$path = $alias->resolve('@root');
// 返回: '/var/www'

// 带子路径的别名
$path = $alias->resolve('@root/app/controllers');
// 返回: '/var/www/app/controllers'

// 非别名路径
$path = $alias->resolve('/absolute/path');
// 返回: '/absolute/path'

// 使用占位符
$path = $alias->resolve('@root/{app_id}/log.txt', ['app_id' => 'myapp']);
// 返回: '/var/www/myapp/log.txt'

// 多个占位符
$path = $alias->resolve('@root/{dir}/{file}.log', [
    'dir' => 'logs',
    'file' => 'app'
]);
// 返回: '/var/www/logs/app.log'

// 缺少上下文键（占位符保留）
$path = $alias->resolve('@root/{app_id}/log.txt', ['other_key' => 'value']);
// 返回: '/var/www/{app_id}/log.txt'
```

**限制**：
- **递归解析**：`resolve()` 方法不会递归解析存储的别名值。如果别名值是另一个别名引用，它不会再次解析
- **存储值中的占位符**：存储的别名值（通过 `set()` 设置）中的占位符在 `resolve()` 期间不会处理
- **上下文键格式**：上下文键不应包含 `{}` 字符，因为它们无法正确匹配占位符

---

### `remove(string $name): void` {#method-remove}

删除别名。

**参数**：
- `$name` (string)：别名名称，必须以 `@` 开头

**抛出**：
- `ManaPHP\Alias\InvalidAliasNameException` - 如果别名名称不以 `@` 开头

**示例**：
```php
$alias->set('@temp', '/tmp');
$alias->remove('@temp');
$alias->has('@temp'); // 返回: false
```

---

### `jsonSerialize(): array` {#method-json-serialize}

返回所有别名用于 JSON 序列化。

**返回**：`array` - 所有已注册的别名作为关联数组。

**注意**：当在 Alias 实例上使用 `json_encode()` 时，会自动调用此方法。

**示例**：
```php
$alias->set('@root', '/var/www');
$alias->set('@app', '/var/www/app');

$json = json_encode($alias);
// 返回: '{"@root":"/var/www","@app":"/var/www/app",...}'
```

---

## 接口：`ManaPHP\AliasInterface` {#interface}

定义别名管理契约的接口。

### 方法

- `all(): array`
- `set(string $name, string $path): string`
- `get(string $name): ?string`
- `has(string $name): bool`
- `resolve(string $path, array $context = []): string`
- `remove(string $name): void`

---

## 平台特定行为 {#platform-specific-behavior}

### Windows {#windows}

- 路径中的反斜杠会转换为正斜杠（`@ns.` 前缀别名除外）
- 正确处理以驱动器字母开头的路径（如 `C:\`）

### Unix/Linux {#unix-linux}

- 路径按原样处理
- 不需要反斜杠转换

---

## 注意事项 {#notes}

1. **一次性解析**：使用别名引用设置别名时（如 `set('@app', '@root/app')`），引用在 `set()` 期间解析一次。存储的值是解析的路径，而不是引用本身。

2. **无递归解析**：`resolve()` 方法不会递归解析存储的别名值。如果需要嵌套别名，请在 `set()` 期间解析它们。

3. **占位符限制**：
   - 占位符仅在 `resolve()` 的输入路径中处理，不在存储的别名值中
   - 上下文键不应包含 `{}` 字符

4. **尾部斜杠**：在 `set()` 期间自动删除路径中的尾部斜杠。

