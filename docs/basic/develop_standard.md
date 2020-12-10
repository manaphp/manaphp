---
     title: 开发规范
---

### 命名规范

开发过程中应该尽量遵循以下命名规范:

* 类文件以`.php`为后缀，使用驼峰法命名，并且首字母大写，例如`EventArgs.php`
* 类的命名空间和文件路径保持一致，例如`App\Controllers\IndexController`类所在的路径应该是`app/Controllers/IndexController.php`
* 确保文件命名和调用大小写一致，ManaPHP在Windows系统上运行会严格检查大小写
* 类名和文件名一致，包括大小写，例如`IndexController`类的文件名是`IndexController.php`
* 函数、配置文件等其他类库文件之外的一般是`.php`为后缀
* 函数的命名使用小写字母和下划线的方式，例如`rest_post`
* 方法的命名使用驼峰法，并且首字母小写或者使用下划线"_"，例如`getUserName`,`_parseType`，通常下划线开头的方法表示非公有方法
* 属性的命名使用下划线"_"分隔，通常下划线开头的属性属于非公有属性
* 以双下划线"__"打头的函数或方法作为魔法方法，例如`__call`
* 常量以大写字母和下划线"_"命名，例如`MANAPHP_CLI`
* 对变量的命名没有强制的规范，可以根据团队规范来进行
* ManaPHP的模板文件默认是以`.sword`或`.phtml`为后缀
* 数据表和字段采用小写加下划线方式命名，例如`rbac_user`表和`user_id`字段是正确写法

> 请确保您的程序文件采用UTF-8编码格式保存，并去掉[BOM](https://en.wikipedia.org/wiki/Byte_order_mark)头，否则可能导致很多意想不到的问题。

### 开发建议

我们给出如下建议，会让您的开发变得更轻松：

* 使用[xdebug](https://xdebug.org/)
* 尽量使用Apache或FPM模式开发
* 遵循框架的命名规范和目录规范
* 多看看日志文件，查找隐患