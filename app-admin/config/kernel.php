<?php
declare(strict_types=1);

return [
    'ManaPHP\KernelInterface' => [
        'bootstrappers' => [
            ManaPHP\Eventing\ListenersInterface::class,
            ManaPHP\Debugging\DebuggerInterface::class,
            ManaPHP\Eventing\TracerInterface::class,
            ManaPHP\Http\Metrics\ExporterInterface::class,
            ManaPHP\Swoole\WorkersInterface::class,
        ],
    ],
];