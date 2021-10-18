<?php

namespace ManaPHP\Configurators;

use ManaPHP\Component;
use ManaPHP\ConfiguratorInterface;
use ManaPHP\Helper\LocalFS;

/**
 * @property-read \ManaPHP\ConfigInterface $config
 */
class MiddlewareConfigurator extends Component implements ConfiguratorInterface
{
    public function configure()
    {
        foreach (LocalFS::glob('@app/Middlewares/?*Middleware.php') as $file) {
            $middleware = 'App\Middlewares\\' . basename($file, ".php");
            $this->container->get($middleware);
        }

        $middlewares = $this->config->get('middlewares', []);
        foreach ($middlewares as $middleware) {
            if (str_contains($middleware, '\\')) {
                $class = $middleware;
            } else {
                $plain = ucfirst($middleware) . 'Middleware';
                $class = "ManaPHP\Http\Middlewares\\$plain";
            }

            $this->container->get($class);
        }
    }
}