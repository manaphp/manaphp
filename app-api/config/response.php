<?php
declare(strict_types=1);

use ManaPHP\Http\Response\Appenders\Etag;
use ManaPHP\Http\Response\Appenders\RequestId;
use ManaPHP\Http\Response\Appenders\ResponseTime;
use ManaPHP\Http\Response\Appenders\Route;

return [
    'ManaPHP\Http\ResponseInterface' => [
        'appenders' => [
            Etag::class,
            RequestId::class,
            ResponseTime::class,
            Route::class,
        ]
    ]
];