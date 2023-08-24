<?php

return [
    ManaPHP\Bootstrappers\DebuggerBootstrapper::class,
    ManaPHP\Bootstrappers\TracerBootstrapper::class => ['tracers' => env('APP_TRACERS', ['*'])],
    ManaPHP\Bootstrappers\FilterBootstrapper::class,
];