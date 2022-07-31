<?php

return [
    ManaPHP\Bootstrappers\ListenerBootstrapper::class,
    ManaPHP\Bootstrappers\TracerBootstrapper::class => ['tracers' => env('APP_TRACERS', ['*'])],
    ManaPHP\Bootstrappers\DebuggerBootstrapper::class,
];