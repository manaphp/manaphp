---
    title: 视图
---

## 简介

视图是应用程序的用户界面，视图通常在HTML文件里嵌入PHP代码，但这些代码仅仅用于展示数据。
`ManaPHP\View`负责管理你的MVC应用程序中的视图(View)层。

## 集成视图到控制器

当控制器执行完某个动作(action)后，ManaPHP自动将执行权转移到视图组件。视图组件将在视图目录中寻找一个目录名与控制器名称相同、文件名与动作相同的文件。例如，如果请求的URL为：*http://blog.com/post/show/1*, ManaPHP解析后会得到下面这些信息：

|    名称    |      值       |
|------------|---------------|
| Host       | blog.com  |
| Controller | post          |
| Action     | show          |
| Parameter  | 1             |

分发器(dispatcher)会执行"PostController"中的"showAction"。一个简单的控制器文件如下：

```php
    namespace App\Controllers;

    use ManaPHP\Mvc\Controller;

    class PostController extends Controller
    {
        public function showAction($post_id)
        {
            $this->view->setVar('post_id', $post_id);
        }
    }
```
`setVar`方法允许我们按照需要创建视图变量，这些变量可以在视图中使用。上面的示例演示了如何传递`post_id`参数到模板中。
## 分层渲染
`ManaPHP\View`支持层次化的视图文件结构即：视图模板及布局模板。视图组件控制模板文件的渲染流程，具体的每个文件渲染由渲染器(renderer)完成。
默认的视图文件目录为`@app/Views`。

|      名称      |             文件            |                                                描述                                                |
|----------------|-----------------------------|----------------------------------------------------------------------------------------------------|
| Action视图文件 | `@views/Post/Show.sword`    | 与Action相关的模板文件，仅当'show'动作执行时才会渲染此模板文件。                                   |
| 控制器布局文件 | `@views/Layouts/Post.sword` | 与控制器相关的模板文件，仅当执行Post控制器时才会渲染此文件，此模板文件由所有的Post控制器动作共享。 |

你需要提供所有上面提到的模板文件，他们将按照如下处理：

```php
        <!-- @app/Views/Post/Show.sword -->

        <h3>This is show view!</h3>

        <p>I have received the parameter {{ $post_id }}</p>
```

```php
    <!-- @app/Views/Layouts/Post.sword -->

    <html>
        <head>
            <title>Example</title>
        </head>
        <body>

        <h2>This is the "post" controller layout!</h2>

        @content()

        </body>
    </html>
```
@content()指令用于把视图文件的内容注入到模板文件。上面的例子输出如下：

```php
    <!-- @app/Views/Layouts/Post.sword -->

    <html>
        <head>
            <title>Example</title>
        </head>
        <body>

        <h2>This is the "posts" controller layout!</h2>

        <!-- @app/Views/Post/Show.sword -->

        <h3>This is show view!</h3>

        <p>I have received the parameter 1</p>

        </body>
    </html>
```

## 选择视图
`ManaPHP\View`由`ManaPHP\Mvc\Application`管理时，默认的视图是由当前执行的什么控制器及什么动作决定的，你也可以使用`ManaPHP\Mvc\View::render()`方法改变这一行为。

```php
    namespace App\Controllers;

    use ManaPHP\Mvc\Controller;

    class ProductController extends Controller
    {
        public function indexAction()
        {
            // 选择 "@views/Product/List" 渲染
            return $this->view->render('list');
        }
    }
```
## 禁用视图
如果你不需要通过视图产生输出页面，你可以禁止视图组件的相关功能：

```php
    namespace App\Controllers;

    use ManaPHP\Mvc\Controller;

    class SessionController extends Controller
    {
        public function closeAction()
        {
            //HTTP重向
            $this->response->redirect('/');
        }
    }
```

## 使用局部模板
局部模板是另一种把渲染流程拆解成更简单的易于管理的模板片断的方法，通过使用局部模板，你可以把某一特定相关的视图拆分成单独模板文件。
把局部模板当成子过程是一种使用它的方法：把模板拆分的更易于理解。
比如我们可能会有这样一个模板：

```php
    <div class="top"><?php partial('Shared/AdBanner'); ?></div>

    <div class="content">
        <h1>Robots</h1>

        <p>Check out our specials for robots:</p>
        ...
    </div>

    <div class="footer"><?php partial('Shared/Footer'); ?></div>
```

我们可以传递一个数组作为`partial()`的第二个参数，这个数组中的变量/值对只在局部模板中可见。


```php

    <?php partial('Shared/AdBanner', ['id' => $site->id, 'size' => 'big']); ?>
```