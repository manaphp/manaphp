<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics;

use ManaPHP\Di\Attribute\Autowired;

abstract class AbstractCollector implements CollectorInterface
{
    #[Autowired] protected FormatterInterface $formatter;
}