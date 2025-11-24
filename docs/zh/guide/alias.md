# Alias 指南

Alias 组件为 ManaPHP 应用程序提供了一种便捷的路径别名管理方式。它允许您为长的文件系统路径定义简短、易记的名称，并在需要时解析它们。

## 概述 {#overview}

路径别名是以 `@` 开头的字符串标记，代表文件或目录路径。它们用于：

- 使路径更易读、更易维护
- 支持跨平台路径处理（Windows/Unix）
- 支持使用占位符进行动态路径解析
- 简化配置文件中的路径管理

## 基本用法 {#basic-usage}

### 设置别名 {#setting-aliases}

在大多数情况下，您可以在应用程序的配置文件中配置别名。Kernel 会在应用程序启动时自动注册它们。

#### 配置文件 {#configuration-file}

在 `config/app.php` 中配置别名：

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

Kernel 会在应用程序启动时自动注册这些别名。您可以使用绝对路径、相对路径或引用其他别名（如 `@root/app`）。

#### 编程方式设置 {#programmatic-setting}

您也可以使用 `set()` 方法以编程方式设置别名：

```php
use ManaPHP\Alias;

$alias = new Alias();

// 使用绝对路径设置别名
$alias->set('@root', '/var/www/myapp');

// 使用相对路径设置别名
$alias->set('@app', 'app');

// 设置引用其他别名的别名
$alias->set('@views', '@root/app/views');
```

**注意**：别名名称必须以 `@` 开头。当您设置一个引用其他别名的别名（如 `@root/app`）时，它会立即解析并存储最终路径。

### 获取别名值 {#getting-alias-values}

使用 `get()` 方法获取别名的值：

```php
$rootPath = $alias->get('@root'); // 返回: '/var/www/myapp'
$appPath = $alias->get('@app');   // 返回: 'app'
```

### 检查别名是否存在 {#checking-alias-existence}

使用 `has()` 方法检查别名是否存在：

```php
if ($alias->has('@root')) {
    // 别名存在
}
```

### 解析路径 {#resolving-paths}

`resolve()` 方法将别名路径转换为实际的文件系统路径：

```php
// 解析简单别名
$path = $alias->resolve('@root');
// 返回: '/var/www/myapp'

// 解析带子路径的别名
$path = $alias->resolve('@root/app/controllers');
// 返回: '/var/www/myapp/app/controllers'

// 解析非别名路径（原样返回）
$path = $alias->resolve('/absolute/path');
// 返回: '/absolute/path'
```

### 使用占位符 {#using-placeholders}

您可以在路径中使用占位符，这些占位符会被上下文值替换：

```php
$alias->set('@root', '/var/www');

// 使用占位符解析
$path = $alias->resolve('@root/{app_id}/log.txt', ['app_id' => 'myapp']);
// 返回: '/var/www/myapp/log.txt'

// 多个占位符
$path = $alias->resolve('@root/{dir}/{file}.log', [
    'dir' => 'logs',
    'file' => 'app'
]);
// 返回: '/var/www/logs/app.log'
```

**提示**：占位符仅在 `resolve()` 中有效，在 `set()` 中无效。使用简单的上下文键，不要包含 `{}` 字符。

## 常见模式 {#common-patterns}

### 应用程序结构 {#application-structure}

使用别名的典型应用程序结构：

```php
$alias->set('@root', __DIR__);
$alias->set('@app', '@root/app');
$alias->set('@config', '@root/config');
$alias->set('@runtime', '@root/runtime');
$alias->set('@public', '@root/public');
$alias->set('@views', '@app/views');
$alias->set('@controllers', '@app/controllers');
```

### 动态日志路径 {#dynamic-log-paths}

使用占位符实现动态路径：

```php
$alias->set('@runtime', '/var/www/runtime');

// 使用动态应用 ID 解析
$logPath = $alias->resolve('@runtime/{app_id}/app.log', [
    'app_id' => 'myapp'
]);
// 返回: '/var/www/runtime/myapp/app.log'
```

### 删除别名 {#removing-aliases}

当不再需要别名时，可以删除它：

```php
$alias->remove('@oldAlias');
```

### 获取所有别名 {#getting-all-aliases}

获取所有已注册的别名：

```php
$allAliases = $alias->all();
// 返回: ['@root' => '/var/www', '@app' => '/var/www/app', ...]
```

## 最佳实践 {#best-practices}

1. **使用描述性名称**：选择能清楚表明其用途的别名名称（如 `@root`、`@app`、`@config`）

2. **层次化组织**：使用嵌套别名构建逻辑路径结构（如 `@app` → `@views` → `@controllers`）

3. **仅在 `resolve()` 中使用占位符**：占位符在 `resolve()` 调用中有效，在 `set()` 中无效

4. **保持上下文键简单**：不要在上下文键中使用 `{}` 字符

5. **处理错误**：在处理用户输入时，始终将别名操作包装在 try-catch 块中

## 错误处理 {#error-handling}

### 无效的别名名称 {#invalid-alias-name}

如果您尝试设置、获取或删除一个无效名称（不以 `@` 开头）的别名，会抛出 `InvalidAliasNameException`：

```php
try {
    $alias->set('invalid', '/path');
} catch (ManaPHP\Alias\InvalidAliasNameException $e) {
    // 处理错误
}
```

### 别名不存在 {#alias-not-found}

如果您尝试解析一个不存在的别名，会抛出 `AliasNotExistException`：

```php
try {
    $alias->resolve('@nonexistent');
} catch (ManaPHP\Alias\AliasNotExistException $e) {
    // 处理错误
}
```

## 完整示例 {#complete-example}

以下是一个完整的示例，展示如何在应用程序中使用别名：

```php
use ManaPHP\Alias;

$alias = new Alias();

// 设置应用程序结构
$alias->set('@root', __DIR__);
$alias->set('@app', '@root/app');
$alias->set('@config', '@root/config');
$alias->set('@runtime', '@root/runtime');

// 使用别名
$configPath = $alias->resolve('@config/app.php');
$logPath = $alias->resolve('@runtime/{app_id}/app.log', [
    'app_id' => 'myapp'
]);

// 检查和获取
if ($alias->has('@root')) {
    $root = $alias->get('@root');
    echo "根目录: $root\n";
}
```

## 下一步 {#next-steps}

有关详细的 API 文档，包括所有方法参数、返回类型、异常和技术细节，请参阅 [Alias API 参考](../reference/alias.md)。

