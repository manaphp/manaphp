---
    title: Alias
---

## 简介

**别名**用来表示`文件路径`、`URL`、`命名空间`，这样就可以避免在代码中硬编码。别名必须以`@`字符开头。ManaPHP预定义了大量的别名。
例如, 别名`@manaphp`表示ManaPHP框架本身所在目录，而`@data`表示应用数据文件所在的目录。

## 定义

可以通过调用`$alias->set()`来定义别名:
```php
    //定义一个文件系统路径别名
    $alias->set('@foo','/path/to/foo');

    //定义一个URL别名
    $alias->set('@bar','http://www.example.com');

    //定义一个命名空间别名
    $alias->set('@ns.plugins','App\Plugins');
```

> *注意*: 别名不必一定指向已经存在的物理路径、URL、命名空间。

别名通常在配置文件`@config/app.php`中定义。示例如下:
```php
return [
    'id' => 'app',
    'aliases' => [
        '@upload' => '@public/upload'
    ],
```

## 解析

通过调用 `$alias->resolve($path)`或来解析根别名和派生别名。解析是通过替换派生别名中的根别名部分得到的。

```php
    <?php
    echo $alias->resolve('@foo');               // 输出 '/path/to/foo'
    echo $alias->resolve('@bar');               // 输出 'http://www.example.com'
    echo $alias->resolve('@foo/bar/file.php');  // 输出 '/path/to/foo/bar/file.php'
```
> *注意* $alias->resolve()并不检查结果路径所指向的资源是否真实存在。

  通过使用别名技术可以解决应用中路径维护的难题，所以别名服务是ManaPHP框架的核心服务。
  别名在ManaPHP的很多地方都可以被正确识别，无需调用`$alias->resolve()`来解析。例如, `ManaPHP\Cache\Adpater\File::$cacheDir`能同时接受文件路径或指定文件路径的别名，因为通过@前缀能够区分它们。

```php
    $cache = new \ManaPHP\Cache\Adapter\File(['cacheDir'=>'@data/cache']);
```

alias支持占位符，有两种，一种是随机数，另一种是时间格式
```php
$alias->resolve('@tmp/{10}.log'); 输出 /var/www/html/tmp/314e673f77.log
$alias->resolve('@tmp/{Ymd}.log'); 输出 /var/www/html/tmp//tmp/20180901.log
```

## 预定义

ManaPHP预定义了一系列别名来简化常用路径和命名空间的使用:
 * `@manaphp` 框架所在的目录
 * `@public` public目录
 * `@app` 应用程序所在目录
 * `@ns.app` App的根命名空间
 * `@views` 视图文件所在目录
 * `@root` ROOT目录
 * `@data` data目录
 * `@tmp` tmp目录
 * `@config` config所在目录
 * `@web` url前缀

## 助手函数

为了便于使用提供了`path()`函数。
```php
path('@data/foo');
```