<?php
declare(strict_types=1);

return [
    'Psr\Log\LoggerInterface' =>
        ['class'     => 'ManaPHP\Logging\Logger',
         'level'     => env('LOGGER_LEVEL', 'info'),
         'appenders' => [
             \ManaPHP\Logging\Appender\FileAppender::class],
        ]
];