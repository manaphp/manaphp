<?php
declare(strict_types=1);

namespace ManaPHP\Data\Model;

use ManaPHP\Data\ModelInterface;

interface ThoseInterface
{
    public function get(string $class): ModelInterface;
}