<?php
declare(strict_types=1);

namespace ManaPHP\Model;

interface ModelManagerInterface
{
    public function getTable(string $model): string;

    public function getConnection(string $model): string;

    public function getPrimaryKey(string $model): string;

    public function getReferencedKey(string $model): string;

    public function getFields(string $model): array;

    public function getColumnMap(string $model): array;

    public function getFillable(string $model): array;

    public function getDateFormat(string $model): string;

    public function getIntFields(string $model): array;

    public function getRules(string $model): array;
}