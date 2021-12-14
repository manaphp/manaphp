<?php
declare(strict_types=1);

namespace ManaPHP\Bootstrappers;

use ManaPHP\BootstrapperInterface;
use ManaPHP\Component;
use ManaPHP\Helper\LocalFS;

class MiddlewareBootstrapper extends Component implements BootstrapperInterface
{
    protected array $middlewares = [];

    public function __construct(array $options = [])
    {
        if (isset($options['middlewares'])) {
            $this->middlewares = $options['middlewares'];
        }
    }

    public function bootstrap(): void
    {
        /** @var \ManaPHP\Http\Middleware $middleware */

        foreach ($this->middlewares as $definition) {
            $plain = ucfirst($definition) . 'Middleware';
            $class = "ManaPHP\Http\Middlewares\\$plain";

            $middleware = $this->container->get($class);
            $middleware->listen();
        }

        foreach (LocalFS::glob('@app/Middlewares/?*Middleware.php') as $file) {
            $middleware = $this->container->get('App\Middlewares\\' . basename($file, ".php"));
            $middleware->listen();
        }

        foreach (LocalFS::glob('@app/Areas/*', GLOB_ONLYDIR) as $item) {
            $area = basename($item);
            foreach (LocalFS::glob("$item/Middlewares/?*Middleware.php") as $file) {
                $middleware = $this->container->get("App\\Areas\\$area\\Middlewares\\" . basename($file, '.php'));
                $middleware->listen();
            }
        }
    }
}