<?php
declare(strict_types=1);

namespace ManaPHP\Mongodb\Model;

interface InferenceInterface
{
    public function primaryKey(string $entityClass): string;

    public function fields(string $entityClass): array;

    public function intFields(string $entityClass): array;

    public function fieldTypes(string $entityClass): array;
}