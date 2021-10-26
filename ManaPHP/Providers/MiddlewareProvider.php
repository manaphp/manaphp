<?php

namespace ManaPHP\Providers;

use ManaPHP\Helper\LocalFS;
use ManaPHP\Provider;

/**
 * @property-read \ManaPHP\ConfigInterface $config
 */
class MiddlewareProvider extends Provider
{
    public function boot()
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