<?php
declare(strict_types=1);

return [
    'ManaPHP\Http\Metrics\ExporterInterface' => [
        'collectors' => [
            'ManaPHP\Http\Metrics\Collectors\HttpRequestDurationCollector',
            'ManaPHP\Http\Metrics\Collectors\HttpRequestsTotalCollector',
            'ManaPHP\Http\Metrics\Collectors\ServerStatsCollector',
            'ManaPHP\Http\Metrics\Collectors\VersionCollector',
            'ManaPHP\Http\Metrics\Collectors\CoroutineStatsCollector',
            'ManaPHP\Http\Metrics\Collectors\CoroutineOptionsCollector',
            'ManaPHP\Http\Metrics\Collectors\HttpResponseSizeCollector',
            'ManaPHP\Http\Metrics\Collectors\MemoryUsageCollector',
            'ManaPHP\Http\Metrics\Collectors\RedisCommandCollector',
            'ManaPHP\Http\Metrics\Collectors\SqlStatementCollector',
            'ManaPHP\Http\Metrics\Collectors\RedisGetResponseSizeCollector',
            'ManaPHP\Http\Metrics\Collectors\RedisGetCommandDurationCollector',
        ],
    ],
];