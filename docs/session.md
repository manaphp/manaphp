---
    title: Session
---

# Storing data in Session

The `ManaPHP\Http\Session` provides object-oriented wrappers to access session data.

Reasons to use this component instead of raw-sessions:

* You can easily isolate session data across applications on the same domain
* Intercept where session data is set/get in your application
* Change the session adapter according to the application needs

## Starting the Session
Some applications are session-intensive, almost any action that performs requires access to session data. There are others who access session data casually.
Thanks to the service container, we can ensure that the session is accessed only when it's clearly needed:

```php
    <?php

    $this->_dependencyInjector->setShared('session', function () {
        return new ManaPHP\Http\Session\Adapter\File();
    });
```

## Storing/Retrieving data in Session
From a controller, a view or any other component that extends `ManaPHP\Component` you can access the session service
and store items and retrieve them in the following way:

```php
    <?php

    use ManaPHP\Mvc\Controller;

    class UserController extends Controller
    {
        public function indexAction()
        {
            // Set a session variable
            $this->session->set('user_name', 'Michael');
        }

        public function welcomeAction()
        {
            // Check if the variable is defined
            if ($this->session->has('user_name')) {

                // Retrieve its value
                $user_name = $this->session->get('user_name');
            }
        }
    }
```
## Removing/Destroying Sessions
It's also possible remove specific variables or destroy the whole session:

```php
    <?php

    use ManaPHP\Mvc\Controller;

    class UserController extends Controller
    {
        public function removeAction()
        {
            // Remove a session variable
            $this->session->remove('user_name');
        }

        public function logoutAction()
        {
            // Destroy the whole session
            $this->session->destroy();
        }
    }
```

## Session Bags

`ManaPHP\Http\Session\Bag` is a component that helps separating session data into "namespaces".
Working by this way you can easily create groups of session variables into the application. By only setting the variables in the "bag",
it's automatically stored in session:

```php
    <?php

    $user       = new ManaPHP\Http\Session\Bag('user',$di);
    $user->set('name','Kimbra Johnson');
    $user->set('age',22);
```

## Persistent Data in Components
Controller, components and classes that extends `ManaPHP\Component` may inject
a `ManaPHP\Http\Session\Bag`. This class isolates variables for every class.
Thanks to this you can persist data between requests in every class in an independent way.

```php
    <?php

    use ManaPHP\Mvc\Controller;

    class UserController extends Controller
    {
        public function indexAction()
        {
            // Create a persistent variable "name"
            $this->persistent->set('name', 'Laura');
        }

        public function welcomeAction()
        {
            if ($this->persistent->has('name')) {
                echo 'Welcome, ', $this->persistent->get('name');
            }
        }
    }
```

In a component:

```php
    <?php

    use ManaPHP\Mvc\Controller;

    class Security extends Component
    {
        public function auth()
        {
            // Create a persistent variable "name"
            $this->persistent->set('name','Laura');
        }

        public function getAuthName()
        {
            return $this->persistent->get('name');
        }
    }
```

The data added to the session (`$this->session`) are available throughout the application, while persistent (`$this->persistent`)
can only be accessed in the scope of the current class.

## Implementing your own adapters
The `ManaPHP\Http\Session\AdapterInterface` interface must be implemented in order to create your own session adapters or extend the existing ones.
