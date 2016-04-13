<?php
// This file is not a CODE, it makes no sense and won't run or validate
// Its AST serves IDE as DATA source to make advanced type inference decisions.

namespace PHPSTORM_META {                                // we want to avoid the pollution
    $STATIC_METHOD_TYPES = [                                 // we make sections for scopes
        \ManaPHP\DiInterface::getShared('') => [           // STATIC call key to make static (1) & dynamic (2) calls work
            'dispatcher' instanceof \ManaPHP\Mvc\Dispatcher,
            'router' instanceof \ManaPHP\Mvc\RouterInterface,
            'request' instanceof \ManaPHP\Http\RequestInterface,
            'response' instanceof \ManaPHP\Http\ResponseInterface,
            'cookies' instanceof \ManaPHP\Http\Response\CookiesInterface,
            'session' instanceof \ManaPHP\Http\SessionInterface,
            'eventsManager' instanceof \ManaPHP\Event\ManagerInterface,
            'db' instanceof \ManaPHP\DbInterface,
            'modelsManager' instanceof \ManaPHP\Mvc\Model\ManagerInterface,
            'modelsMetadata' instanceof \ManaPHP\Mvc\Model\MetadataInterface,
            'di' instanceof \ManaPHP\Di | \ManaPHP\DiInterface,
            'view' instanceof \ManaPHP\Mvc\ViewInterface,
            'authorization' instanceof \ManaPHP\Auth\AuthorizationInterface,
            'application' instanceof \ManaPHP\ApplicationInterface,
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
 * @xglobal $view ManaPHP\Mvc\ViewInterface
 */
/**
 * @var \ManaPHP\Mvc\View          $view
 * @var \ManaPHP\Mvc\View\Renderer $renderer
 */
$view = null;
$renderer = null;
unset($view, $renderer);

