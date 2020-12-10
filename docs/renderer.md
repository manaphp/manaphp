---
    title: 渲染器
---

# Using Renderer

Renderer help designers to render view. `ManaPHP\Renderer` allows you to use other template engines instead of plain PHP , [Smarty](http://www.smarty.net/), or Html.

Using a different template engine, usually requires complex text parsing using external PHP libraries in order to generate the final output
for the user. This usually increases the number of resources that your application will use.

This component uses adapters, these help ManaPHP to speak with those external template engines in a unified way, let's see how to do that integration.

## Creating your own Template Engine Adapter

There are many template engines, which you might want to integrate or create one of your own. The first step to start using an external template engine is create an adapter for it.

A template engine adapter is a class that acts as bridge between `ManaPHP\Mvc\View\Renderer` and the template engine itself.
Usually it only needs two methods implemented: `__construct()` and `render()`. The first one receives the DI container used by the application.

The method `render()` accepts an absolute path to the view file and the view parameters. You could read or require it when it's necessary.

```php
    <?php

    use ManaPHP\Mvc\View\Renderer\EngineInterface;

    class MyTemplateAdapter implements EngineInterface
    {
        /**
         * Adapter constructor
         *
         * @param \ManaPHP\Di $di
         */
        public function __construct($di)
        {
            // Initialize here the adapter
        }

        /**
         * Renders a view using the template engine
         *
         * @param string $path
         * @param array $params
         */
        public function render($path, $params)
        {
            // Render the view
            // ...
        }
    }
```

## Changing the Template Engine
You can replace or add more a template engine from the controller as follows:

```php

    <?php

    namespace Application\Home\Controllers;

    use ManaPHP\Mvc\Controller;

    class PostController extends Controller
    {
        public function indexAction()
        {
            // Set the engine
            $this->renderer->registerEngines(
                array(
                    ".my-html" => "MyTemplateAdapter"
                )
            );
        }

        public function showAction()
        {
            // Using more than one template engine
            $this->renderer->registerEngines(
                array(
                    ".my-html" => 'MyTemplateAdapter',
                    ".phtml"   => 'ManaPHP\Mvc\View\Engine\Php'
                )
            );
        }
    }
```

You can replace the template engine completely or use more than one template engine at the same time. The method `ManaPHP\Mvc\View\Renderer::registerEngines()`
accepts an array containing data that define the template engines. The key of each engine is an extension that aids in distinguishing one from another.
Template files related to the particular engine must have those extensions.

The order that the template engines are defined with `ManaPHP\Mvc\View\Renderer::registerEngines()` defines the relevance of execution. If
`ManaPHP\Mvc\View\Renderer` finds two views with the same name but different extensions, it will only render the first one.

If you want to register a template engine or a set of them for each request in the application. You could register it when the view service is created:

```php

    <?php

    use ManaPHP\Mvc\View\Renderer;

    // Setting up the renderer component
    $di->setShared('renderer', function () {

        $renderer = new Renderer();

        $renderer->registerEngines(
            array(
                ".my-html" => 'MyTemplateAdapter'
            )
        );

        return $view;
    });
```language
```