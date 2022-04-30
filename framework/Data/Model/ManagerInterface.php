<?php
declare(strict_types=1);

namespace ManaPHP\Data\Model;

interface ManagerInterface
{
    public function getTable(string $model): string;

    public function getConnection(string $model): string;

    public function getPrimaryKey(string $model): string;

    public function getForeignedKey(string $model): string;
}