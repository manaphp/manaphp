---
    title: 路由器
---

## 简介

路由器组件允许您定义映射到控制器和动作的路由。路由器通过解析URI以确定此信息。

## 定义路由

`ManaPHP\Router`组件提供了路由器功能。 在MVC模式下，您可以定义路由并将它们映射到您需要的/controller/action。路由定义如下：

```php    
    $router = new \ManaPHP\Router();

    $router->add("/admin/users/my-profile", array(
        "controller" => "user",
        "action" => "profile"
      ));

    $router->add("/admin/users/change-password", "user::changePassword");
    $router->handle();
```

`add()`方法的第一个参数是你想要匹配的模式，可选的第二个参数是一组路径。在这种情况下，如果URI是/admin/users/my-profile，则将执行具有其动作`profile`的`user`控制器。
重要的是要记住路由器不执行控制器和操作，它只收集这些信息来通知正确的组件（即`ManaPHP\Dispatcher`）这是它应该执行的控制器/动作。

应用程序可以有许多路径，逐个定义路径可能是一项繁琐的任务。在这些情况下，我们可以创建更灵活的路由：

```php
    $router = new \ManaPHP\Router();

    $router->add("/admin/:controller/a/:action/:params");
```

在上面的示例中，我们使用通配符来使路由对许多URI有效。例如，通过访问以下URL（/admin/user/a/delete/dave）将产生：

| Controller | user          |
|------------|:--------------|
| Action     | delete        |
| Parameter  | dave          |

`add()`方法接收一个模式，该模式可以选择具有预定义的占位符和正则表达式修饰符。使用的正则表达式语法与[`PCRE正则表达式`](http://www.php.net/manual/en/book.pcre.php)相同。
请注意，没有必要添加正则表达式分隔符。

> 所有路由模式都不区分大小写，并且所有路由模式都必须以正斜杠字符（/）开头。

第二个参数定义匹配的部分应如何绑定到控制器/动作/参数。匹配部分是由括号（圆括号）分隔的占位符或子模式。在上面给出的示例中，匹配的第一个子模式（：控制器）是路径的控制器部分，第二个是动作，依此类推。

这些占位符有助于编写对开发人员更易读且更易于理解的正则表达式。支持以下占位符：

| Placeholder          | Regular Expression          | Usage                                                                                      |
|----------------------|:----------------------------|:-------------------------------------------------------------------------------------------|
| `/:controller` | `/([a-z0-9_-]+)`      | Matches a valid controller name with alpha-numeric characters only                                     |
| `/:action`     | `/([a-z0-9_-]+)`      | Matches a valid action name with alpha-numeric characters only                                         |
| `/:int`        | `/([0-9]+)`           | Matches an integer parameter                                                                           |
    
控制器名称是camelized，这意味着删除了字符`_`，下一个字符是大写的。例如，blog_comment转换为BlogComment。

在内部，所有已定义的路由以相反的顺序遍历，直到`ManaPHP\Router`找到与给定URI匹配并处理它的那个，而忽略其余的。

## 带名称的参数

下面的示例演示了如何定义路由参数的名称：
```php
    $router->add(
        "/news/{year:[0-9]{4}}/{month:[0-9]{2}}/{day:[0-9]{2}}/:params",
        array(
            "controller" => "post",
            "action"     => "show",
        )
    );
```
在上面的示例中，路径未定义`控制器`或`操作`部分。这些部件被更换具有固定值（`post`和`show`）。用户将不知道真正分派的控制器根据要求。
在控制器内部，可以按如下方式访问这些命名参数：

```php
    class PostController extends ManaPHP\Mvc\Controller
    {
        public function indexAction()
        {

        }

        public function showAction()
        {
            // Get "year" parameter
            $year = $this->dispatcher->getParam("year");

            // Get "month" parameter
            $month = $this->dispatcher->getParam("month");

            // Get "day" parameter
            $day = $this->dispatcher->getParam("day");

            // ...
        }
    }
```

请注意，参数的值是从调度程序获得的。发生这种情况是因为它是最终与应用程序驱动程序交互的组件。
此外，还有另一个示例创建命名参数作为模式的一部分：

```php
    $router->add(
        "/documentation/{chapter}/{name}.{type:[a-z]+}",
        array(
            "controller" => "documentation",
            "action"     => "show"
        )
    );
```
您可以像以前一样访问它们的值：
```php
    use ManaPHP\Mvc\Controller;

    class DocumentationController extends Controller
    {
        public function showAction()
        {
            // Get "name" parameter
            $name = $this->dispatcher->getParam("name");

            // Get "type" parameter
            $type = $this->dispatcher->getParam("type");

            // ...
        }
    }
```

## 简短语法

如果您不喜欢使用数组来定义路径路径，则还可以使用替代语法。以下示例产生相同的结果：

```php
    // Short form
    $group->add("/posts/{year:[0-9]+}/{title:[a-z\-]+}", "Post::show");

    // Array form
    $group->add(
        "/posts/{year:[0-9]+}/{title:[a-z\-]+}",
        array(
           "controller" => "post",
           "action"     => "show",
        )
    );
```

支持以下简短语法:

| pattern                    | sample            |
|----------------------------|:------------------|
| controller::action         | user::list        |
| controller                 | user::index       |

## HTTP方法限制

使用简单的`add()`添加路由时，将为任何HTTP方法启用路由。有时我们可以将路由限制为特定方法，这在创建RESTful应用程序时尤其有用：

```php
    $group->addGet("/products/{product_id:\d+}", "Product::edit");
    $group->addPost("/products/{product_id:\d+}", "ProductController::updateAction");
    $group->add("/products/{product_id:\d+}", "Product::update",["POST", "PUT"]);
```

## 匹配路由

必须将有效的URI传递给路由器，以便它可以处理它并找到匹配的路由。默认情况下，路由URI取自重写引擎模块创建的`$ _GET ['_ url']`变量。
一些与ManaPHP配合得很好的重写规则是：

```apache
    RewriteEngine On
    RewriteCond   %{REQUEST_FILENAME} !-d
    RewriteCond   %{REQUEST_FILENAME} !-f
    RewriteRule   ^((?s).*)$ index.php?_url=/$1 [QSA,L]
```

在此配置中，对不存在的文件或文件夹的任何请求都将发送到`index.php`。

## 默认路由

`ManaPHP\Router`有一个默认行为，提供一个非常简单的路由，总是需要一个匹配以下模式的URI：`/:controller/:action/:params`
例如，对于这样的URL `http：//www.manaphp.com/documentation/show/about.html`，此路由器将按如下方式对其进行解析:

|part        | value         |
|:-----------|:--------------|
| Controller | documentation |
| Action     | show          |
| Parameter  | about.html    |

## URI Sources

默认情况下，URI信息是从`$_GET['_url']`变量获得的，这是由Rewrite-Engine传递给ManaPHP的，
或者您可以手动将URI传递给`handle()`方法：

```php
    $router->handle('/some/route/to/handle');
```