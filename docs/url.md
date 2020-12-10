---
    title: URL生成
---

# Generating URLs
`ManaPHP\Mvc\Url` is the component responsible of generate URLs in a ManaPHP application.

## Setting a base URI
Depending of which directory of your document root your application is installed, it may have a base URI or not.

For example, if your document root is **/var/www/htdocs** and your application is installed in **/var/www/htdocs/invo** then your
baseUri will be '**/invo**'. If you are using a VirtualHost or your application is installed on the document root, then your baseUri is **''**.
Execute the following code to know the base URI detected by ManaPHP:

```php
    <?php

    use ManaPHP\Mvc\Url;

    $url = new Url();
    echo $url->getBaseUri();
```
By default, ManaPHP automatically detect your baseUri, but if you want to increase the performance of your application is recommended setting up it manually:

```php
    <?php

    use ManaPHP\Mvc\Url;

    $url = new Url();

    // Setting a relative base URI
    $url->setBaseUri('/invo');

    // Setting a full domain as base URI
    $url->setBaseUri('//my.domain.com');

    // Setting a full domain as base URI
    $url->setBaseUri('http://my.domain.com/my-app');
```

Usually, this component must be registered in the Dependency Injector container, so you can set up it there:

```php
    <?php

    use ManaPHP\Mvc\Url;

    $di->set('url', function () {
        $url = new Url();
        $url->setBaseUri('/invo');
        return $url;
    });
```
## Generating URIs
If you are using the `Router` with its default behavior. Your application is able to match routes based on the
following pattern: /:controller/:action/:params. Accordingly it is easy to create routes that satisfy that pattern (or any other
pattern defined in the router) passing a string to the method "get":

```php

    <?php echo $url->get("/products/save"); ?>
```
## Producing URLs without mod_rewrite
You can use this component also to create URLs without mod_rewrite:

```php
    <?php

    use ManaPHP\Mvc\Url;

    $url = new Url();

    // Pass the URI in $_GET["_url"]
    $url->setBaseUri('/invo/index.php?_url=');

    // This produce: /invo/index.php?_url=/products/save
    echo $url->get("/products/save");
```
## Implementing your own URL Generator
The `ManaPHP\Mvc\UrlInterface` interface must be implemented to create your own URL generator replacing the one provided by ManaPHP.