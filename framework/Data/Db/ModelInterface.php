<?php
declare(strict_types=1);

namespace ManaPHP\Data\Db;

use ManaPHP\Data\QueryInterface;

interface ModelInterface extends \ManaPHP\Data\ModelInterface
{
    public static function query(?string $alias = null): QueryInterface;

    public static function insertBySql(string $sql, array $bind = []): int;

    public static function deleteBySql(string $sql, array $bind = []): int;

    public static function updateBySql(string $sql, array $bind = []): int;
}