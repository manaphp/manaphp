<?php
declare(strict_types=1);

namespace ManaPHP\Data\Model;

interface ManagerInterface
{
    public function getTable(string $model): string;

    public function getConnection(string $model): string;

    public function getPrimaryKey(string $model): string;

    public function getForeignedKey(string $model): string;

    public function getFields(string $model): array;

    public function getJsonFields(string $model): array;

    public function getAutoIncrementField(string $model): ?string;

    public function getColumnMap(string $model): array;

    public function getFillable(string $model): array;

    public function getDateFormat(string $model): string;
}