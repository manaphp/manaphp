<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics;

use ManaPHP\Http\ResponseInterface;

interface ExporterInterface
{
    public function export(): ResponseInterface;
}