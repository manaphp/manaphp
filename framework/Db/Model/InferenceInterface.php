<?php
declare(strict_types=1);

namespace ManaPHP\Db\Model;

interface InferenceInterface
{
    public function primaryKey(string $entityClass): string;

    public function intFields(string $entityClass): array;
}