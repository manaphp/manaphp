---
    title: 请求
---

## 简介

每个HTTP请求都包含有关请求的相关信息，例如标题数据，文件，变量等。基于Web的应用程序需要解析该信息以便提供正确的信息
回复请求者。 `ManaPHP\Http\Request`封装了请求的信息，允许您以面向对象的方式访问它。

```php
    use ManaPHP\Http\Request;

    // Getting a request instance
    $request = new Request();

    // Check whether the request was made with method POST
    if ($request->isPost()) {
        // Check whether the request was made with Ajax
        if ($request->isAjax()) {
            echo "Request was made using POST and AJAX";
        }
    }
```

## 在控制器中访问request组件

访问`request`组件最常见位置是控制器的动作。

```php
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

## 获取上传文件

另一个常见任务是文件上传。 `ManaPHP\Http\Request`提供了一种面向对象的方式来实现这个任务：

```php
    use ManaPHP\Mvc\Controller;

    class PostController extends Controller
    {
        public function uploadAction()
        {
            // Check if the user has uploaded files
            if ($this->request->hasFiles()) {

                // Print the real file names and sizes
                foreach ($this->request->getFiles() as $file) {

                    // Print file details
                    echo $file->getName(), " ", $file->getSize(), "\n";

                    // Move the file into the application
                    $file->moveTo('files/' . $file->getName());
                }
            }
        }
    }
```

`ManaPHP\Http\Request::getFiles（）`返回的每个对象都是`ManaPHP\Http\Request\File`类。

使用`$ _FILES`超全局数组提供相同的行为。

`ManaPHP\Http\Request\File`仅封装与请求一起上传的每个文件相关的信息。
