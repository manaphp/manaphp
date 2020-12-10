# Flashing Messages
Flash messages are used to notify the user about the state of actions he/she made or simply show information to the users.
These kinds of messages can be generated using this component.

## Adapters
This component makes use of adapters to define the behavior of the messages after being passed to the Flasher:

| Adapter | Description                                                                                   | API                                    |
|:--------|:----------------------------------------------------------------------------------------------|:---------------------------------------|
| Direct  | Directly outputs the messages passed to the flasher                                           | `ManaPHP\Flash\Adapter\Direct`      |
| Session | Temporarily stores the messages in session, then messages can be printed in the next request  | `ManaPHP\Flash\Adapter\Session`     |

## Usage
Usually the Flash Messaging service is requested from the services container,
if you're using `ManaPHP\Di\FactoryDefault` then `ManaPHP\Flash\Adapter\Direct` is automatically registered as "flash" service:

```php
    <?php

    // Set up the flash service
    $di->set('flash', function () {
        return new ManaPHP\Flash\Adapter\Direct();
    });
```

This way, you can use it in controllers or views by injecting the service in the required scope:

```php
    <?php

    use ManaPHP\Mvc\Controller;

    class PostsController extends Controller
    {
        public function indexAction()
        {

        }

        public function saveAction()
        {
            $this->flash->success("The post was correctly saved!");
        }
    }
```

There are four built-in message types supported:

```php
    <?php

    $this->flash->error("too bad! the form had errors");
    $this->flash->success("yes!, everything went very smoothly");
    $this->flash->notice("this a very important information");
    $this->flash->warning("best check yo self, you're not looking too good.");
```
## Printing Messages

Messages sent to the flash service are automatically formatted with HTML:

```html
    <div class="flash-error-message">too bad! the form had errors</div>
    <div class="flash-success-message">yes!, everything went very smoothly</div>
    <div class="flash-notice-message">this a very important information</div>
    <div class="flash-warning-message">best check yo self, you're not looking too good.</div>
```
As you can see, CSS classes are added automatically to the DIVs. These classes allow you to define the graphical presentation
of the messages in the browser. The CSS classes can be overridden, for example, if you're using Twitter bootstrap, classes can be configured as:

```php
    <?php

    // Register the flash service with custom CSS classes
    $di->set('flash', function () {
        $flash = new ManaPHP\Flash\Adapter\Direct(
            array(
                'error'   => 'alert alert-danger',
                'success' => 'alert alert-success',
                'notice'  => 'alert alert-info',
                'warning' => 'alert alert-warning'
            )
        );

        return $flash;
    });
```

Then the messages would be printed as follows:

```html

    <div class="alert alert-danger">too bad! the form had errors</div>
    <div class="alert alert-success">yes!, everything went very smoothly</div>
    <div class="alert alert-info">this a very important information</div>
    <div class="alert alert-warning">best check yo self, you're not looking too good.</div>
```
## Implicit Flush vs. Session
Depending on the adapter used to send the messages, it could be producing output directly, or be temporarily storing the messages in session to be shown later.
When should you use each? That usually depends on the type of redirection you do after sending the messages. For example,
if you make a "forward" is not necessary to store the messages in session, but if you do a HTTP redirect then, they need to be stored in session:

```php
    <?php

    use ManaPHP\Mvc\Controller;

    class ContactController extends Controller
    {
        public function indexAction()
        {

        }

        public function saveAction()
        {
            // Store the post

            // Using direct flash
            $this->flash->success("Your information was stored correctly!");

            // Forward to the index action
            return $this->dispatcher->forward(
                array(
                    "action" => "index"
                )
            );
        }
    }
```

Or using a HTTP redirection:

```php
    <?php

    use ManaPHP\Mvc\Controller;

    class ContactController extends Controller
    {
        public function indexAction()
        {

        }

        public function saveAction()
        {
            // Store the post

            // Using session flash
            $this->flashSession->success("Your information was stored correctly!");

            // Make a full HTTP redirection
            return $this->response->redirect("contact/index");
        }
    }
```
In this case you need to manually print the messages in the corresponding view:

```php
    <!-- app/views/contact/index.phtml -->

    <p><?php $this->flashSession->output() ?></p>
```
The attribute 'flashSession' is how the flash was previously set into the dependency injection container.