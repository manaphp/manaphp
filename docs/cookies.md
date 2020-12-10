---
    title: Cookies管理
---

# Cookies Management
[Cookies](http://php.net/manual/en/features.cookies.php) are a very useful way to store small pieces of data on the client's machine that can be retrieved even
if the user closes his/her browser. `ManaPHP\Http\Cookies`
acts as a global bag for cookies. Cookies are stored in this bag during the request execution and are sent
automatically at the end of the request.

## Basic Usage

You can set/get cookies by just accessing the 'cookies' service in any part of the application where services can be
accessed:

```php
    <?php

    use ManaPHP\Mvc\Controller;

    class SessionController extends Controller
    {
        public function loginAction()
        {
            // Check if the cookie has previously set
            if ($this->cookies->has('remember-me')) {

                // Get the cookie value
                $rememberMe = $this->cookies->get('remember-me');
            }
        }

        public function startAction()
        {
            $this->cookies->set('remember-me', 'some value', time() + 15 * 86400);
        }

        public function logoutAction()
        {
            // Delete the cookie
            $this->cookies->delete('remember-me');
        }
    }
```
## Encryption/Decryption of Cookies
by default, cookies are not encrypted.
if you want to prevent unauthorized users from seeing the cookies' content in the client(browser),
you can add `!` character before the cookie name,
which will be automatically encrypted before being sent to the client and are decrypted when retrieved from the user.

You can use cookies in the following way:

```php

    <?php
    namespace Application\Home\Controllers;

    use ManaPHP\Http\Cookies;

    class IndexController extends ManaPHP\Mvc\Controller{
        public function indexAction(){
            // use plain cookie
            $this->cookies->get('login_time');

            // use encrypted cookie
            $this->cookies->get('!login_time');
        }
    }

```
If you wish to use encryption, a global key must be set in the 'crypt' service:

```php
    <?php
    namespace Application\Application;
    use ManaPHP\Security\Crypt;

    class Application extends \ManaPHP\Mvc\Application{
        protected function registerServices(){

            $this->setShared('crypt', function () {
                return new Crypt('#1dj8$=dp?.ak//j1V$');// Use your own key!
            });
         }
    }

```


>    Sending cookies data without encryption to clients including complex objects structures, resultsets,
    service information, etc. could expose internal application details that could be used by an attacker
    to attack the application. If you do not want to use encryption, we highly recommend you only send very
    basic cookie data like numbers or small string literals.
