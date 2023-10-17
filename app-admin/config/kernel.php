<?php
declare(strict_types=1);

return [
    'ManaPHP\KernelInterface' => [
        'bootstrappers' => [
            ManaPHP\Debugging\DebuggerBootstrapper::class,
            ManaPHP\Http\FilterBootstrapper::class,
            ManaPHP\Eventing\TracerBootstrapper::class,
        ],
    ],
];