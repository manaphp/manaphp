<?php
declare(strict_types=1);

namespace ManaPHP\Data\Db\Model;

use ManaPHP\Data\Db\ModelInterface;

interface MetadataInterface
{
    public function getAttributes(string $model): array;

    public function getPrimaryKeyAttributes(string $model): array;

    public function getAutoIncrementAttribute(string $model): ?string;

    public function getIntTypeAttributes(string $model): array;
}
