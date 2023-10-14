<?php
declare(strict_types=1);

return [
    \ManaPHP\Kernel\BootstrapperLoaderInterface::class => [
        'bootstrappers' => [
            ManaPHP\Bootstrappers\DebuggerBootstrapper::class,
            ManaPHP\Bootstrappers\FilterBootstrapper::class,
            ManaPHP\Bootstrappers\TracerBootstrapper::class
        ]
    ]
];