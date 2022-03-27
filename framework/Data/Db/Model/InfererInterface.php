<?php
declare(strict_types=1);

namespace ManaPHP\Data\Db\Model;

interface InfererInterface
{
    public function primaryKey(string $model): string;

    public function fields(string $model): array;

    public function intFields(string $model): array;
}