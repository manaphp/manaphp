<?php

return [
    'id'            => 'api',
    'env'           => env('APP_ENV', 'prod'),
    'debug'         => env('APP_DEBUG', false),
    'aliases'       => [],
    'factories'     => require __DIR__ . '/factories.php',
    'dependencies'  => require __DIR__ . '/dependencies.php',
    'bootstrappers' => require __DIR__ . '/bootstrappers.php',
    'filters'       => require __DIR__ . '/filters.php',
];