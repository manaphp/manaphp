---
    title: 组件容器
---

## 简介

ManaPHP组件容器是一个用于管理类依赖和执行依赖注入的强大工具。依赖注入听上去很花哨，其实质是通过构造函数或者通过`php`的魔术方法注入到实例中。

下面的例子有点长，但是我们试图用它来解释为何ManaPHP使用依赖注入。

首先，假设我们需要开发一个名叫`SomeComponent`的组件。它对数据库连接有依赖。

在这第一个示例中，数据库连接是在组件内部创建的。这种方法是不切实际的，因为它只能以一个固定的方法使用数据库连接，我们不能修改连接参数或连接逻辑。

```php
    class SomeComponent
    {
        /**
         * 连接实例是硬编码在组件中的，因此，外部替换或修改它的行为是非常困难的
         */
        public function someDbTask()
        {
            $connection = new Connection(
                array(
                    "host"     => "localhost",
                    "username" => "root",
                    "password" => "secret",
                    "dbname"   => "invo"
                )
            );

            // ...
        }
    }

    $some = new SomeComponent();
    $some->someDbTask();
```

为了解决这个问题，我们创建一个`setter`方法用来注入数据库连接。现在看来，它好像是一个很好的解决方案：

```php
    class SomeComponent
    {
        protected $_connection;

        /**
         * 设置数据库连接
         */
        public function setConnection($connection)
        {
            $this->_connection = $connection;
        }

        public function someDbTask()
        {
            $connection = $this->_connection;

            // ...
        }
    }

    $some = new SomeComponent();

    // 创建数据库连接
    $connection = new Connection(
        array(
            "host"     => "localhost",
            "username" => "root",
            "password" => "secret",
            "dbname"   => "invo"
        )
    );

    // 把数据库连接注入到组件中
    $some->setConnection($connection);

    $some->someDbTask();
```

现在考虑我们在应用程序的不同部分使用此组件，然后我们需要多次创建连接才能将其传递给组件。使用某种全局注册机制，我们获取同一个连接实例而不是一次又一次地创建它可以解决这个问题：
```php
    class Registry
    {
        /**
         * 返回新数据库连接
         */
        public static function getConnection()
        {
            return new Connection(
                array(
                    "host"     => "localhost",
                    "username" => "root",
                    "password" => "secret",
                    "dbname"   => "invo"
                )
            );
        }
    }

    class SomeComponent
    {
        protected $_connection;

        /**
         * 设置数据库连接
         */
        public function setConnection($connection)
        {
            $this->_connection = $connection;
        }

        public function someDbTask()
        {
            $connection = $this->_connection;

            // ...
        }
    }

    $some = new SomeComponent();

    // 传入由注册容器生成的数据库连接
    $some->setConnection(Registry::getConnection());

    $some->someDbTask();
```

现在，让我们假设必须在组件中实现两个方法，第一个总是需要创建一个新连接，第二个总是需要使用共享连接：
```php
    class Registry
    {
        protected static $_connection;

        /**
         * 创建一个新的连接
         */
        protected static function _createConnection()
        {
            return new Connection(
                array(
                    "host"     => "localhost",
                    "username" => "root",
                    "password" => "secret",
                    "dbname"   => "invo"
                )
            );
        }

        /**
         * 总是只创建一次连接，并返回它
         */
        public static function getSharedConnection()
        {
            if (self::$_connection===null) {
                $connection = self::_createConnection();
                self::$_connection = $connection;
            }

            return self::$_connection;
        }

        /**
         * 总是返回一个新的示例
         */
        public static function getNewConnection()
        {
            return self::_createConnection();
        }
    }

    class SomeComponent
    {
        protected $_connection;

        /**
         * 设置数据库连接实例
         */
        public function setConnection($connection)
        {
            $this->_connection = $connection;
        }

        /**
         * 这个方法总是使用共享的数据库连接
         */
        public function someDbTask()
        {
            $connection = $this->_connection;

            // ...
        }

        /**
         * 这个方法总是使用新的数据库连接
         */
        public function someOtherDbTask($connection)
        {

        }
    }

    $some = new SomeComponent();

    // 注入共享数据库连接
    $some->setConnection(Registry::getSharedConnection());

    $some->someDbTask();

    // 我们总是传入一个新的数据库连接
    $some->someOtherDbTask(Registry::getNewConnection());
```

到目前为止，我们已经看到依赖注入如何解决我们的问题，将依赖项作为参数传递，在代码内部创建它们,使我们的应用程序更易于维护和解耦。但是，从长远来看，这种形式的依赖注入有一些缺点。

例如，如果组件有许多依赖项，我们将需要创建多个setter参数来传递依赖项或创建一个构造函数，使用许多参数传递它们，另外创建依赖项在使用组件之前，每次都使我们的代码不像我们希望的那样可维护：

```php
    // 创建新的实例或由全局注册机制获取共享实例
    $connection = new Connection();
    $session    = new Session();
    $fileSystem = new FileSystem();
    $filter     = new Filter();
    $selector   = new Selector();

    // 通过构造函数传入依赖
    $some = new SomeComponent($connection, $session, $fileSystem, $filter, $selector);

    // ... Or using setters

    $some->setConnection($connection);
    $some->setSession($session);
    $some->setFileSystem($fileSystem);
    $some->setFilter($filter);
    $some->setSelector($selector);
```
想想我们是否必须在应用程序的许多部分创建此对象。
将来，如果我们不需要任何依赖项，我们需要遍历整个代码库来删除我们注入代码的任何构造函数或setter中的参数。
要解决这个问题，我们再次返回全局注册表来创建组件。但是，它在创建之前添加了一个新的抽象层：
```php
    class SomeComponent
    {
        // ...

        /**
         * 定义一个工厂方法来创建组件依赖
         */
        public static function factory()
        {
            $connection = new Connection();
            $session    = new Session();
            $fileSystem = new FileSystem();
            $filter     = new Filter();
            $selector   = new Selector();

            return new self($connection, $session, $fileSystem, $filter, $selector);
        }
    }
```

现在我们发现自己回到了开始的地方，我们再次构建组件内部的依赖关系！我们必须找到一个解决方案阻止我们反复陷入不良行为。

解决这些问题的一种实用而优雅的方法是使用容器来实现依赖。容器充当全局注册表我们之前看过。使用依赖项容器作为获取依赖关系的桥梁，可以降低复杂性：

```php
    use ManaPHP\Di;

    class SomeComponent
    {
        protected $_di;

        public function __construct($di)
        {
            $this->_di = $di;
        }

        public function someDbTask()
        {
            //获取一个新的数据库连接实例
            $connection = $this->_di->get('db');
        }

        public function someOtherDbTask()
        {
            //获取一个共享的数据库连接实例
            $connection = $this->_di->getShared('db');

            //这个方法还需要一个输入过滤组件
            $filter = $this->_di->get('filter');
        }
    }

    $di = new Di();

    // 在容器中注册一个"db"实例
    $di->set('db', "mysql://root:123456@localhost/test");

    //在容器中注册一个"filter"组件
    $di->set('filter', function () {
        return new Filter();
    });

    // 在容器中注册一个"session"组件
    $di->set('session', function () {
        return new Session();
    });

    //把容器做为唯一参数
    $some = new SomeComponent($di);

    $some->someDbTask();
```

该组件现在可以在需要时简单地访问它所需的组件，如果它不需要组件，它甚至不会被初始化，节约资源。
该组件现在高度分离。例如，我们可以替换创建连接的方式，他们的行为或他们的任何其他方面，不会影响组件。

## 我们的方法

`ManaPHP\Di` 是一个实现依赖注入和组件定位的组件，它本身就是一个容器。

由于ManaPHP高度分离，因此`ManaPHP\Di`对于整合框架的不同组件至关重要。开发人员可以还使用此组件注入依赖项并管理应用程序中使用的不同类的全局实例。

基本上，该组件实现了[Inversion of Control]模式。它降低了复杂性，因为只有获取组件中所需依赖项的一种方法。
此外，这种模式增加了代码的可测试性，从而使其不易出错。

## 在容器中注册组件

框架本身或开发人员可以注册组件。当组件A需要组件B（或其类的实例）进行操作时，它可以从容器中请求组件B，而不是创建新的实例组件B.

这种工作方式给我们带来了许多好处:

* 我们可以轻松地用我们自己或第三方创建的组件替换组件。
* 我们完全控制对象初始化，允许我们在将它们传递给组件之前根据需要设置这些对象。
* 我们可以以结构化和统一的方式获取组件的全局实例。

可以使用几种类型的定义注册组件：

```php
    use ManaPHP\Http\Request;

    // 创建一个新的容器
    $di = new ManaPHP\Di();

    // 通过类名配置一个共享组件
    $di->setShared("request", 'ManaPHP\Http\Request');

    //通过匿名函数配置一个组件，实例总是延迟创建
    $di->set("request", function () {
        return new Request();
    });

    // 通过组件实例配置一个共享组件
    $di->setShared("request", new Request());

    //通过数组方式配置一个共享组件
    $di->setShared("request",["class" => 'ManaPHP\Http\Request']);
```

在上面的示例中，当框架需要访问请求数据时，它将使用在容器中标识为`request`的组件。容器反过来将返回所需组件的实例。开发人员可能最终在他/她需要时替换组件。
用于设置/注册组件的每种方法（在以上示例中说明）具有优点和缺点。这取决于开发人员以及将指定使用哪一个的特定要求。

通过字符串设置组件很简单，但缺乏灵活性。使用数组设置组件提供了更大的灵活性，但是可以实现代码更复杂。 匿名函数是两者之间的良好平衡，但可能导致比预期更多的维护。

`ManaPHP\Di`为它存储的每项组件提供延迟加载。除非开发人员选择直接实例化对象并存储它在容器中，存储在其中的任何对象（通过数组，字符串等）将被延迟加载，即仅在请求时被实例化。

如前所述，有几种方法可以注册组件

### 字符串

此类型需要有效类的名称，返回指定类的对象，如果未加载该类，则将使用自动加载器对其进行实例化。
这种类型的定义不允许为类构造函数或参数指定参数：

```php
    $di->setShared('request', 'ManaPHP\Http\Request');
```

### 对象

此类型需要一个对象。由于对象不需要按原样解析已经是一个对象了，可以说它实际上并不是依赖注入，但是如果要强制返回的依赖项始终是有用的话相同的对象：

```php
    use ManaPHP\Http\Request;

    $di->setShared('request', new Request());
```

### 匿名函数

这种方法提供了更大的自由度来构建依赖关系，但是，很难从外部更改一些参数，而不必完全更改依赖项的定义：
```php
    $di->set("request", function () {
        return new Request();
    });
```

## 解析组件

从容器中获取组件只需调用`get`或`getShared`方法即可。将返回该组件的新实例：

```php
    $request = $di->getShared("request");
```

## 以静态方式访问DI

如果需要，您可以通过以下方式访问在静态函数中创建的最新DI：

```php
    use ManaPHP\Di;

    class SomeComponent
    {
        public static function someMethod()
        {
            // Get the session service
            $session = Di::getDefault()->getSession();
        }
    }
```

## 实现自己的DI

必须实现`ManaPHP\DiInterface`接口以创建自己的DI，替换ManaPHP提供的DI或扩展当前的DI。

[Inversion of Control]: http://en.wikipedia.org/wiki/Inversion_of_control
[singletons]: http://en.wikipedia.org/wiki/Singleton_pattern
