<?php

return [
    ManaPHP\Bootstrappers\TracerBootstrapper::class => ['tracers' => env('APP_TRACERS', ['*'])],
    ManaPHP\Bootstrappers\DebuggerBootstrapper::class,
    ManaPHP\Bootstrappers\ListenerBootstrapper::class,
    ManaPHP\Bootstrappers\FilterBootstrapper::class,
];