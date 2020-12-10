---
     title: 入口文件
---

ManaPHP采用单一入口模式进行部署和访问，无论完成什么功能，一个应用都有一个统一的入口。

应该说，所有应用都是从入口文件开始的，并且不同应用的入口文件基本上是完全一样的。

典型的入口文件如下:

```php
<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$app = new \App\Application();
$app->main();
```
