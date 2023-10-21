<?php
declare(strict_types=1);

return [
    'ManaPHP\Http\HandlerInterface' => [
        'class'       => 'ManaPHP\Rest\Handler',
        'middlewares' => [
            \ManaPHP\Http\Middlewares\VerbsMiddleware::class,
        ],
    ],
];