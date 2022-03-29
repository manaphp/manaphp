<?php
declare(strict_types=1);

namespace ManaPHP\Data\Mongodb\Model;

interface InferrerInterface
{
    public function primaryKey(string $model): string;

    public function fields(string $model): array;

    public function intFields(string $model): array;

    public function fieldTypes(string $model): array;
}