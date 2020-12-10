---
    title: 控制器
---

## 简介

控制器可以将相关的HTTP请求处理封装到一个类中进行处理。控制器存放在`app/Controllers`目录中。

例如，当您访问这样的URL时：http：//localhost/post/show/2016默认情况下ManaPHP会分解每个
像这样的部分：

|Name                   |Value           |
|-----------------------|:---------------|
| **Controller**        | post           |
| **Action**            | show           |
| **Parameter**         | 2016           |

在这种情况下，PostController将处理此请求。控制器必须具有后缀“Controller”，同时操作后缀“Action”。控制器的样本如下：

```php
    namespace App\Controllers;

    use ManaPHP\Mvc\Controller;

    class PostController extends Controller
    {
        public function indexAction()
        {

        }

        public function showAction()
        {

        }
    }
```

您可以通过以下方式从其名称中获取任意参数：
```php
    namespace App\Controllers;

    use ManaPHP\Mvc\Controller;

    class PostController extends Controller
    {
        public function indexAction()
        {

        }

        public function showAction()
        {
            $year      = $this->dispatcher->getParam(0);
            $postTitle = $this->dispatcher->getParam(1);
        }
    }
```

## 注入组件

如果控制器继承了`ManaPHP\Mvc\Controller`，那么它可以轻松访问应用中的容器。例如，如果我们注册了这样的组件：

```php
    use ManaPHP\Di;

    $di = new Di();

    $di->setShared('storage', function () {
        return new Storage('/some/directory');
    });
```
然后，我们可以通过以下几种方式访问​​该组件：

```php
    use ManaPHP\Mvc\Controller;

    class FileController extends Controller
    {
        public function saveAction()
        {
            // Injecting the service by just accessing the property with the same name
            $this->storage->save('/some/file');

            // Accessing the service from the DI
            $this->di->get('storage')->save('/some/file');
        }
    }
```

## Request

`request`组件是`ManaPHP \ Http \ Request`的实例。

```php
    namespace App\Controllers;

    use ManaPHP\Mvc\Controller;

    class PostController extends Controller
    {
        public function indexAction()
        {

        }

        public function saveAction()
        {
            // Check if request has made with POST
            if ($this->request->isPost()) {
                // Access POST data
                $customerName = $this->request->get("name");
                $customerBorn = $this->request->get("born");
            }
        }
    }
```

## Response

`response`包含一个`ManaPHP\Http\Response`，表示将要发送回客户端的内容。

```php
    <?php

    namespace App\Controllers;

    use ManaPHP\Mvc\Controller;

    class PostController extends Controller
    {
        public function indexAction()
        {

        }

        public function notFoundAction()
        {
            // Send a HTTP 404 response header
            return $this->response->setStatusCode(404, "Not Found");
        }
    }
```

[DRY]: https://en.wikipedia.org/wiki/Don%27t_repeat_yourself
