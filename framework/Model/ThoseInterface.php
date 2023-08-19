<?php
declare(strict_types=1);

namespace ManaPHP\Model;


interface ThoseInterface
{
    public function get(string $class): ModelInterface;
}