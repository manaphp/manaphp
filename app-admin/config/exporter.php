<?php
declare(strict_types=1);

return [
    'ManaPHP\Http\Metrics\ExporterInterface' => [
        'collectors' => [
            'ManaPHP\Http\Metrics\Collectors\RequestDurationCollector',
            'ManaPHP\Http\Metrics\Collectors\RequestsTotalCollector',
            'ManaPHP\Http\Metrics\Collectors\ServerStatsCollector',
            'ManaPHP\Http\Metrics\Collectors\VersionCollector',
            'ManaPHP\Http\Metrics\Collectors\CoroutineStatsCollector',
            'ManaPHP\Http\Metrics\Collectors\CoroutineOptionsCollector',
            'ManaPHP\Http\Metrics\Collectors\ResponseSizeCollector',
            'ManaPHP\Http\Metrics\Collectors\MemoryUsageCollector',
        ],
    ],
];