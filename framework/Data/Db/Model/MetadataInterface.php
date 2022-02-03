<?php
declare(strict_types=1);

namespace ManaPHP\Data\Db\Model;

use ManaPHP\Data\Db\ModelInterface;

interface MetadataInterface
{
    public function getAttributes(string|ModelInterface $model): array;

    public function getPrimaryKeyAttributes(string|ModelInterface $model): array;

    public function getAutoIncrementAttribute(string|ModelInterface $model): ?string;

    public function getIntTypeAttributes(string|ModelInterface $model): array;
}
