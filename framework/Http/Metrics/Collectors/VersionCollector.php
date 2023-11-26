<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics\Collectors;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\Attribute\Config;
use ManaPHP\Http\Metrics\CollectorInterface;
use ManaPHP\Http\Metrics\FormatterInterface;
use ManaPHP\Version;

class VersionCollector implements CollectorInterface
{
    #[Autowired] protected FormatterInterface $formatter;

    #[Config] protected string $app_version = '';

    public function export(mixed $data): string
    {
        return $this->formatter->gauge(
            'app_version', 1,
            ['lang'       => 'php',
             'php'        => PHP_VERSION,
             'swoole'     => SWOOLE_VERSION,
             'framework'  => Version::get(),
             'deployment' => $this->app_version,
            ],
        );
    }
}