<?php
declare(strict_types=1);

return [
    ManaPHP\Http\FilterBootstrapper::class => [
        'filters' => [
            ManaPHP\Http\Filters\Authorization::class,
        ]],
];