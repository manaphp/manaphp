<?php

namespace ManaPHP\Http;

use ManaPHP\Di\FactoryDefault;

class Factory extends FactoryDefault
{
    public function __construct()
    {
        parent::__construct();

        $this->_definitions = array_merge(
            $this->_definitions, [
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

                'corsPlugin'      => 'ManaPHP\Http\CorsPlugin',
                'csrfPlugin'      => 'ManaPHP\Http\CsrfPlugin',
                'etagPlugin'      => 'ManaPHP\Http\EtagPlugin',
                'httpCachePlugin' => 'ManaPHP\Http\HttpCachePlugin',
                'pageCachePlugin' => 'ManaPHP\Http\PageCachePlugin',
                'rateLimitPlugin' => 'ManaPHP\Http\RateLimitPlugin',
                'requestIdPlugin' => 'ManaPHP\Http\RequestIdPlugin',
                'slowlogPlugin'   => 'ManaPHP\Http\SlowlogPlugin',
                'verbsPlugin'     => 'ManaPHP\Http\VerbsPlugin',

                'aclCommand' => 'ManaPHP\Http\Acl\Command',

                'httpClientTracer' => 'ManaPHP\Http\Client\Tracer',
                'requestTracer'    => 'ManaPHP\Http\Request\Tracer',
            ]
        );

        if (PHP_SAPI === 'cli') {
            if (class_exists('Workerman\Worker')) {
                $this->setShared('httpServer', 'ManaPHP\Http\Server\Adapter\Workerman');
            } elseif (extension_loaded('swoole')) {
                $this->setShared('httpServer', 'ManaPHP\Http\Server\Adapter\Swoole');
            } else {
                $this->setShared('httpServer', 'ManaPHP\Http\Server\Adapter\Php');
            }
        } elseif (PHP_SAPI === 'cli-server') {
            $this->setShared('httpServer', 'ManaPHP\Http\Server\Adapter\Php');
        } else {
            $this->setShared('httpServer', 'ManaPHP\Http\Server\Adapter\Fpm');
        }
    }
}