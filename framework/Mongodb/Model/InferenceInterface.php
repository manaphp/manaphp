<?php
declare(strict_types=1);

namespace ManaPHP\Mongodb\Model;

interface InferenceInterface
{
    public function primaryKey(string $model): string;

    public function fields(string $model): array;

    public function intFields(string $model): array;

    public function fieldTypes(string $model): array;
}