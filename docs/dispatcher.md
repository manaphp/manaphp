---
    title: 分发器
---

# Dispatching Controllers
`ManaPHP\Dispatcher` is the component responsible for instantiating controllers and executing the required actions on them in an MVC application. 

## The Dispatch Loop
This is an important process that has much to do with the MVC flow itself, especially with the controller part. The work occurs within the controller
dispatcher. The controller files are loaded, and instantiated. Then the required actions are executed. If an action forwards the flow to another
controller/action, the controller dispatcher starts again. To better illustrate this, the following example shows approximately the process performed
within `ManaPHP\Dispatcher`:

```php
    <?php

    $finished=false;

    // Dispatch loop
    while (!$finished) {

        $finished = true;

        $controllerClassName = '';
       if ($this->_rootNamespace) {
           $controllerClassName .= $this->_rootNamespace . '\';
       }
       if ($this->_moduleName) {
           $controllerClassName .= $this->_moduleName . '\Controllers\';
       }
       $controllerClassName .= $this->_controllerName . $this->_controllerSuffix;
        // Instantiating the controller class via autoloaders
        $controller = new $controllerClassName();

        // Execute the action
        call_user_func([$controller, $actionName . $this->_actionSuffix]);

        // '$finished' should be reloaded to check if the flow was forwarded to another controller
        //....
    }
```
The code above lacks validations, filters and additional checks, but it demonstrates the normal flow of operation in the dispatcher.

### Dispatch Loop Events
`ManaPHP\Dispatcher` is able to send `event`. Events are triggered using the type `dispatcher`. 
Some events when returning boolean false could stop the active operation. The following events are supported:

| Event Name           | Triggered                                                                                                                                                                                                      | Can stop operation? | Triggered on          |
|----------------------|:---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|:--------------------|:----------------------|
| beforeDispatchLoop   | Triggered before entering in the dispatch loop. At this point the dispatcher don't know if the controller or the actions to be executed exist. The Dispatcher only knows the information passed by the Router. | Yes                 | Listeners             |
| beforeDispatch       | Triggered after entering in the dispatch loop. At this point the dispatcher don't know if the controller or the actions to be executed exist. The Dispatcher only knows the information passed by the Router.  | Yes                 | Listeners             |
| beforeExecuteRoute   | Triggered before executing the controller/action method. At this point the dispatcher has been initialized the controller and know if the action exist.                                                        | Yes                 | Listeners/Controllers |
| afterExecuteRoute    | Triggered after executing the controller/action method. As operation cannot be stopped, only use this event to make clean up after execute the action                                                          | No                  | Listeners/Controllers |
| afterDispatch        | Triggered after executing the controller/action method. As operation cannot be stopped, only use this event to make clean up after execute the action                                                          | Yes                 | Listeners             |
| afterDispatchLoop    | Triggered after exiting the dispatch loop                                                                                                                                                                      | No                  | Listeners             |

The following example demonstrates how to attach listeners to this component:

```php
    <?php

    $this->_dependencyInjector->setShared('dispatcher', function () {
        $dispatcher=new ManaPHP\Mvc\Dispatcher();
        $dispatcher->attachEvent('dispatcher',function($event,$dispatcher){
            //...
        });
        return $dispatcher;
    });
```

An instantiated controller automatically acts as a listener for dispatch events, so you can implement methods as callbacks:

```php
    <?php

    use ManaPHP\Event\Event;
    use ManaPHP\Mvc\Controller;
    use ManaPHP\Dispatcher;

    class PostsController extends Controller
    {
        public function beforeExecuteRoute(Event $event, Dispatcher $dispatcher)
        {
            // Executed before every found action
        }

        public function afterExecuteRoute(Event $event, Dispatcher $dispatcher)
        {
            // Executed after every found action
        }
    }
```
## Forwarding to other actions
The dispatch loop allows us to forward the execution flow to another controller/action. 
This is very useful to check if the user can access to certain action, redirect users to other screens or simply reuse code.

```php
    <?php

    use ManaPHP\Mvc\Controller;

    class PostController extends Controller
    {
        public function indexAction()
        {

        }

        public function saveAction($year, $postTitle)
        {
            // ... Store some product and forward the user

            // Forward flow to the index action
            $this->dispatcher->forward('index');
        }
    }
```
Keep in mind that making a "forward" is not the same as making a HTTP redirect. Although they apparently got the same result.
The "forward" doesn't reload the current page, all the redirection occurs in a single request, 
while the HTTP redirect needs two requests to complete the process.

More forwarding examples:

```php

    <?php

    // Forward flow to another action in the current controller
    $this->dispatcher->forward('search');

    // Forward flow to another action in the current controller
    // passing parameters
    $this->dispatcher->forward([
            'action' => 'search',
            'params' => [1, 2, 3]
        ]);

    // Forward flow to another action in the another controller
    $this->dispatcher->forward('session/add');
```
A forward action accepts the following parameters:

| Parameter      | Triggered                                              |
|----------------|:-------------------------------------------------------|
| controller     | A valid controller name to forward to.                 |
| action         | A valid action name to forward to.                     |
| params         | An array of parameters for the action                  |

## Preparing Parameters

Thanks to the hooks points provided by `ManaPHP\Dispatcher` you can easily adapt your application to any URL schema:

For example, you want your URLs look like: http://example.com/controller/key1/value1/key2/value

Parameters by default are passed as they come in the URL to actions, you can transform them to the desired schema:

```php
    <?php

    $this->_dependencyInjector->setShared('dispatcher', function () {

        $dispatcher=new ManaPHP\Dispatcher();

        $dispatcher->attachEvent('dispatcher:beforeDispatchLoop', function ($event, $dispatcher) {

            $keyParams = array();
            $params    = $dispatcher->getParams();

            // Use odd parameters as keys and even as values
            foreach ($params as $number => $value) {
                if ($number & 1) {
                    $keyParams[$params[$number - 1]] = $value;
                }
            }

            // Override parameters
            $dispatcher->setParams($keyParams, false);
        });

        return $dispatcher;
    });
```
If the desired schema is: http://example.com/controller/key1:value1/key2:value, the following code is required:

```php
    <?php

    $this->_dependencyInjector->setShared('dispatcher', function () {

        $dispatcher=new ManaPHP\Mvc\Dispatcher();

        // Attach a listener
        $dispatcher->attachEvent('dispatcher:beforeDispatchLoop', function ($event, $dispatcher) {

            $keyParams = array();
            $params    = $dispatcher->getParams();

            // Explode each parameter as key,value pairs
            foreach ($params as $value) {
                list($k,$v) = explode(':', $value);
                $keyParams[$k] = $v;
            }

            // Override parameters
            $dispatcher->setParams($keyParams, false);
        });

        return $dispatcher;
    });
```
## Getting Parameters
When a route provides named parameters you can receive them in a controller。

```php
    use ManaPHP\Mvc\Controller;

    class PostController extends Controller
    {
        public function indexAction()
        {

        }

        public function saveAction()
        {
            // Get the post's title passed in the URL as parameter
            // or prepared in an event
            $title = $this->dispatcher->getParam('title');

            // ...
        }
    }
```
## Preparing actions
You can also define an arbitrary schema for actions before be dispatched.

### Camelize action names
If the original URL is: http://example.com/admin/products/show_latest_products,
and for example you want to camelize 'show_latest_products' to 'ShowLatestProducts',
the following code is required:

```php
    <?php

    $this->_dependencyInjector->setShared('dispatcher', function () {

        $dispatcher=new ManaPHP\Mvc\Dispatcher();

        // Camelize actions
        $dispatcher->attachEvent('dispatcher:beforeDispatchLoop', function ($event, $dispatcher) {
            $dispatcher->setActionName(ManaPHP\Utility\Text::camelize($dispatcher->getActionName()));
        });

        return $dispatcher;
    });
```
### Remove legacy extensions
If the original URL always contains a '.php' extension:

http://example.com/admin/products/show_latest_products.php
http://example.com/admin/products/index.php

You can remove it before dispatch the controller/action combination:

```php
    <?php

    $this->_dependencyInjector->setShared('dispatcher', function () {
        $dispatcher=new ManaPHP\Dispatcher();

        // Remove extension before dispatch
        $dispatcher->attachEvent('dispatcher:beforeDispatchLoop', function ($event, $dispatcher) {

            // Remove extension
            $action = preg_replace('/\.php$/', '', $dispatcher->getActionName());

            // Override action
            $dispatcher->setActionName($action);
        });

        return $dispatcher;
    });
```

## Implementing your own Dispatcher

The `ManaPHP\DispatcherInterface` interface must be implemented to create your own dispatcher replacing the one provided by ManaPHP.
