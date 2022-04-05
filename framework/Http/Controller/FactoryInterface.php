<?php
declare(strict_types=1);

namespace ManaPHP\Http\Controller;

use ManaPHP\Http\Controller;

interface FactoryInterface
{
    public function get(string $controller): Controller;
}