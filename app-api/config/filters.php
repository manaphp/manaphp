<?php
declare(strict_types=1);

return [
    ManaPHP\Bootstrappers\FilterBootstrapper::class => [
        'filters' => [
            ManaPHP\Filters\EtagFilter::class,
            ManaPHP\Filters\VerbsFilter::class,
        ]]
];