<?php
declare(strict_types=1);

use ManaPHP\Http\Response\Appenders\EtagAppender;
use ManaPHP\Http\Response\Appenders\RequestIdAppender;
use ManaPHP\Http\Response\Appenders\ResponseTimeAppender;
use ManaPHP\Http\Response\Appenders\RouteAppender;

return [
    'ManaPHP\Http\ResponseInterface' => [
        'appenders' => [
            EtagAppender::class,
            RequestIdAppender::class,
            ResponseTimeAppender::class,
            RouteAppender::class,
        ]
    ]
];