<?php
declare(strict_types=1);

namespace ManaPHP\Db;

use ManaPHP\Query\QueryInterface;

interface ModelInterface extends \ManaPHP\Model\ModelInterface
{
    public static function query(?string $alias = null): QueryInterface;
}