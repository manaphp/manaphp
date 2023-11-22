<?php
declare(strict_types=1);

return [
    'ManaPHP\Swoole\ProcessesInterface' => [
        'processes' => [
            \App\Processes\TestProcess::class,
        ],
    ]
];