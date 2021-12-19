<?php
declare(strict_types=1);

namespace ManaPHP\Controller;

use ManaPHP\Controller;

interface InvokerInterface
{
    public function invoke(Controller $controller, string $method):mixed;
}