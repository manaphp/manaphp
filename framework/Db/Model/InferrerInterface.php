<?php
declare(strict_types=1);

namespace ManaPHP\Db\Model;

interface InferrerInterface
{
    public function primaryKey(string $model): string;

    public function fields(string $model): array;

    public function intFields(string $model): array;
}