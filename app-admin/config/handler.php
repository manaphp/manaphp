<?php
declare(strict_types=1);

return [
    'ManaPHP\Http\HandlerInterface' => [
        'class'       => 'ManaPHP\Mvc\Handler',
        'middlewares' => [
            \ManaPHP\Http\Middlewares\RequestIdMiddleware::class,
            \ManaPHP\Http\Middlewares\AuthorizationMiddleware::class,
        ],
    ],
];