---
    title: 加密
---

# Encryption/Decryption

ManaPHP provides encryption facilities via the `ManaPHP\Security\Crypt` component.
This class offers simple object-oriented wrappers to the [OpenSSL](http://www.php.net/manual/en/book.openssl.php) PHP's encryption library.

This component provides secure encryption using AES-128-CBC.

## Basic Usage
This component is designed to provide a very simple usage:

```php
    <?php

    use ManaPHP\Security\Crypt;

    // Create an instance
    $crypt     = new Crypt();

    $key       = 'This is a secret key.';
    $text      = 'This is the text that you want to encrypt.';

    $encrypted = $crypt->encrypt($text, $key);

    echo $crypt->decrypt($encrypted, $key);
```
You can use the same instance to encrypt/decrypt several times:

```php

    <?php

    use ManaPHP\Security\Crypt;

    // Create an instance
    $crypt = new Crypt('This is a secret text');

    $texts = array(
        'This is first secret text',
        'This is second secret text'
    );

    foreach ($texts as $text) {

        // Perform the encryption
        $encrypted = $crypt->encrypt($text);

        // Now decrypt
        echo $crypt->decrypt($encrypted);
    }
```
## Base64 Support
In order for encryption to be properly transmitted (emails) or displayed (browsers) [base64-encode](http://php.net/manual/en/function.base64-encode.php) is usually applied to encrypted texts:

```
    <?php

    use ManaPHP\Security\Crypt;

    // Create an instance
    $crypt   = new Crypt('le password');

    $text    = 'This is a secret text';

    $encrypt = base64_encode($crypt->encrypt($text));

    echo $crypt->decrypt(base64_decode($encrypt));
```
## Setting up an Encryption service
You can set up the encryption component in the services container in order to use it from any part of the application:

```
    <?php
    namespace Application;
    use ManaPHP\Security\Crypt;

    class Application extends \ManaPHP\Mvc\Application{
       protected function registerServices(){
            $this->setShared('crypt', function () {
                    // Set a global encryption key
                    return new Crypt('%31.1e$i86e$f!8jz');
                });
       }
    }
```

Then, for example, in a controller you can use it as follows:

```
    <?php

    use ManaPHP\Mvc\Controller;

    class SecretController extends Controller
    {
        public function saveAction()
        {
            $secret = new Secret();

            $text = $this->request->getPost('text');

            $secret->content = $this->crypt->encrypt($text);

            if ($secret->save()) {
                $this->flash->success('Secret was successfully created!');
            }
        }
    }
```