<?php

namespace ManaPHP\Http;

use ManaPHP\Helper\LocalFS;

class Provider extends \ManaPHP\Di\Provider
{
    protected $definitions
        = [
            'router'         => 'ManaPHP\Http\Router',
            'dispatcher'     => 'ManaPHP\Http\Dispatcher',
            'url'            => 'ManaPHP\Http\Url',
            'response'       => 'ManaPHP\Http\Response',
            'request'        => 'ManaPHP\Http\Request',
            'session'        => 'ManaPHP\Http\Session\Adapter\File',
            'cookies'        => 'ManaPHP\Http\Cookies',
            'captcha'        => 'ManaPHP\Http\Captcha',
            'authorization'  => 'ManaPHP\Http\Authorization',
            'globalsManager' => 'ManaPHP\Http\Globals\Manager',
            'aclBuilder'     => 'ManaPHP\Http\Acl\Builder',

            'aclCommand'  => 'ManaPHP\Cli\Commands\AclCommand',
            'areaCommand' => 'ManaPHP\Cli\Commands\AreaCommand',

            'httpClientTracer' => 'ManaPHP\Http\Client\Tracer',
            'requestTracer'    => 'ManaPHP\Http\Request\Tracer',
            'dispatcherTracer' => 'ManaPHP\Http\Dispatcher\Tracer',
        ];

    public function __construct()
    {
        $this->definitions['httpServer'] = (function () {
            if (PHP_SAPI === 'cli') {
                if (class_exists('Workerman\Worker')) {
                    return 'ManaPHP\Http\Server\Adapter\Workerman';
                } elseif (extension_loaded('swoole')) {
                    return 'ManaPHP\Http\Server\Adapter\Swoole';
                } else {
                    return 'ManaPHP\Http\Server\Adapter\Php';
                }
            } elseif (PHP_SAPI === 'cli-server') {
                return 'ManaPHP\Http\Server\Adapter\Php';
            } else {
                return 'ManaPHP\Http\Server\Adapter\Fpm';
            }
        })();
    }

    public function boot($container)
    {
        foreach (LocalFS::glob('@app/Middlewares/?*Middleware.php') as $file) {
            $middleware = 'App\Middlewares\\' . basename($file, ".php");
            $container->get($middleware);
        }

        $middlewares = $container->get('config')->get('middlewares', []);
        foreach ($middlewares as $middleware) {
            if (str_contains($middleware, '\\')) {
                $class = $middleware;
            } else {
                $plain = ucfirst($middleware) . 'Middleware';
                $class = "ManaPHP\Http\Middlewares\\$plain";
            }

            $container->get($class);
        }
    }
}