<?php
declare(strict_types=1);

namespace ManaPHP\Http\Metrics;

use ManaPHP\BootstrapperInterface;
use ManaPHP\Http\ResponseInterface;

interface ExporterInterface extends BootstrapperInterface
{
    public function export(): ResponseInterface;
}