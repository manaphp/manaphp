<?php
declare(strict_types=1);

namespace ManaPHP\Data\Db;

interface PdoMakerInterface
{
    public function make(array $parameters): mixed;
}