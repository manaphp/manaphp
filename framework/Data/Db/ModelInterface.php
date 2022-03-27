<?php
declare(strict_types=1);

namespace ManaPHP\Data\Db;

use ManaPHP\Data\QueryInterface;

interface ModelInterface extends \ManaPHP\Data\ModelInterface
{
    public static function query(?string $alias = null): QueryInterface;
}