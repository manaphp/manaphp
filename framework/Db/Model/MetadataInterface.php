<?php
declare(strict_types=1);

namespace ManaPHP\Db\Model;

interface MetadataInterface
{
    public function getAttributes(string $model): array;

    public function getPrimaryKeyAttributes(string $model): array;

    public function getAutoIncrementAttribute(string $model): ?string;

    public function getIntTypeAttributes(string $model): array;
}
