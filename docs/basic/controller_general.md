---
     title: 控制器
---

我们可以在`app/Controllers`目录下面找到一个`IndexController.php`文件，这就是默认的Index控制器文件。

控制器类的命名方式是：`控制器名`(驼峰法，首字母大写) + `Controller`

控制器文件的命名方式是：`类名` + `.php`(类文件后缀)

默认的欢迎页面其实就是访问`IndexController`控制器的`indexAction`动作，我们修改默认的indexAction动作如下:

```php
namespace App\Controllers;
use ManaPHP\Mvc\Controller;

class IndexController extends Controller {
     public function indexAction(){
          return $this->response->setContent("Hello, world!");
     }
}
```

再次运行应用入口文件，浏览器显示: `hello world!`。

我们再来看下控制器类，IndexController控制器类的开头是命名空间定义：

```php
namespace App\Controllers;
```

命名空间和实际的控制器文件所在路径是一致的，也就是说`App\Controllers\IndexController`类对应的控制器文件位于应用目录下面的`app/Controllers/IndexController.php`

> 命名空间定义必须在所有的PHP代码之前，否则会报错

```php
use ManaPHP\Mvc\Controller;
```

表示引入ManaPHP\Mvc\Controller命名空间便于直接使用。所以
```php
use ManaPHP\Mvc\Controller;
class IndexController extends Controller
```
等同于使用:
```php
class IndexController extends \ManaPHP\Mvc\Controller