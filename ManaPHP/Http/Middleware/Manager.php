<?php

namespace ManaPHP\Http\Middleware;

use ManaPHP\Component;
use ManaPHP\Helper\LocalFS;

/**
 * @property-read \ManaPHP\ConfigInterface $config
 */
class Manager extends Component implements ManagerInterface
{
    public function listen()
    {
        foreach (LocalFS::glob('@app/Middlewares/?*Middleware.php') as $file) {
            /** @var \ManaPHP\Http\Middleware $middleware */
            $middleware = $this->container->get('App\Middlewares\\' . basename($file, ".php"));
            $middleware->listen();
        }

        $middlewares = $this->config->get('middlewares', []);
        foreach ($middlewares as $definition) {
            if (str_contains($definition, '\\')) {
                $class = $definition;
            } else {
                $plain = ucfirst($definition) . 'Middleware';
                $class = "ManaPHP\Http\Middlewares\\$plain";
            }

            $middleware = $this->container->get($class);
            $middleware->listen();
        }
    }
}