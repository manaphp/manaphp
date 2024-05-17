<?php
declare(strict_types=1);

namespace ManaPHP\Db\Model;

interface MetadataInterface
{
    public function getAttributes(string $entityClass): array;

    public function getPrimaryKeyAttributes(string $entityClass): array;

    public function getAutoIncrementAttribute(string $entityClass): ?string;

    public function getIntTypeAttributes(string $entityClass): array;
}
