<?php
// This file is not a CODE, it makes no sense and won't run or validate
// Its AST serves IDE as DATA source to make advanced type inference decisions.

namespace PHPSTORM_META {                                // we want to avoid the pollution
    $STATIC_METHOD_TYPES = [                                 // we make sections for scopes
        \ManaPHP\DiInterface::getShared('') => [           // STATIC call key to make static (1) & dynamic (2) calls work
            'dispatcher' instanceof \ManaPHP\Mvc\Dispatcher,
            'router' instanceof \ManaPHP\RouterInterface,
            'request' instanceof \ManaPHP\Http\RequestInterface,
            'response' instanceof \ManaPHP\Http\ResponseInterface,
            'cookies' instanceof \ManaPHP\Http\CookiesInterface,
            'session' instanceof \ManaPHP\Http\SessionInterface,
            'eventsManager' instanceof \ManaPHP\Event\ManagerInterface,
            'db' instanceof \ManaPHP\DbInterface,
            'modelsManager' instanceof \ManaPHP\Mvc\Model\ManagerInterface,
            'modelsMetadata' instanceof \ManaPHP\Mvc\Model\MetadataInterface,
            'di' instanceof \ManaPHP\Di | \ManaPHP\DiInterface,
            'view' instanceof \ManaPHP\ViewInterface,
            'authorization' instanceof \ManaPHP\Security\AuthorizationInterface,
            'application' instanceof \ManaPHP\ApplicationInterface,
            'alias' instanceof \ManaPHP\AliasInterface,
            'flash' instanceof \ManaPHP\View\FlashInterface,
            'flashSession' instanceof \ManaPHP\View\FlashInterface,
            'captcha' instanceof \ManaPHP\Security\CaptchaInterface,
            'httpClient' instanceof \ManaPHP\Curl\EasyInterface,
            'password' instanceof \ManaPHP\Authentication\PasswordInterface,
            'counter' instanceof \ManaPHP\CounterInterface,
            'cache' instanceof \ManaPHP\CacheInterface,
            'userIdentity' instanceof \ManaPHP\Authentication\UserIdentityInterface,
            'logger' instanceof \ManaPHP\LoggerInterface,
            'configure' instanceof \ManaPHP\Configuration\Configure,
            'settings' instanceof \ManaPHP\Configuration\SettingsInterface,
            'csrfToken' instanceof \ManaPHP\Security\CsrfTokenInterface,
            'paginator' instanceof \ManaPHP\Paginator,
            'viewsCache' instanceof \ManaPHP\Cache\EngineInterface,
            'filesystem' instanceof \ManaPHP\FilesystemInterface,
            'random' instanceof \ManaPHP\Security\RandomInterface,
            'messageQueue' instanceof \ManaPHP\Message\QueueInterface,
            'filter' instanceof \ManaPHP\Http\FilterInterface,
            'url' instanceof \ManaPHP\Mvc\UrlInterface,
            'stopwatch' instanceof \ManaPHP\StopwatchInterface,
            'htmlPurifier' instanceof \ManaPHP\Security\HtmlPurifierInterface,
            'redis' instanceof \ManaPHP\Redis,
        ],
        \ManaPHP\DiInterface::get('') => [           // STATIC call key to make static (1) & dynamic (2) calls work
            '' == '@',
        ],
        new \ServiceLocatorInterface => [                // NEW INSTANCE is to make ArrayAccess (3) style factory work
            "special" instanceof \Exception,
        ],
        \ServiceLocatorInterface::getByPattern('') => [
            "" == "@Iterator",                       // "ignored" == "PatternWith@" substitutes @ with arg value
        ],
        globalFactoryFunction('') => [                   // (4) works also with functions
        ],                                               // if key is not found its used as type name in all cases
    ];
}

/**
 * @xglobal $view ManaPHP\ViewInterface
 */
/**
 * @var \ManaPHP\ViewInterface     $view
 * @var \ManaPHP\Di                    $di
 * @var \ManaPHP\Http\RequestInterface $request
 */
$view = null;
$di = null;
$request = null;
unset($view, $renderer);

class_exists('\Elasticsearch\Client') || class_alias('\stdClass', '\Elasticsearch\Client');