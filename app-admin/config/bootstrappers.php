<?php
declare(strict_types=1);

return [
    \ManaPHP\Kernel\BootstrapperLoaderInterface::class => [
        'bootstrappers' => [
            ManaPHP\Eventing\ListenerBootstrapper::class,
            ManaPHP\Debugging\DebuggerBootstrapper::class,
            ManaPHP\Http\FilterBootstrapper::class,
            ManaPHP\Eventing\TracerBootstrapper::class,
        ],
    ]
];