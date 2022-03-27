<?php
declare(strict_types=1);

namespace ManaPHP\Data\Mongodb;

interface CollectionGatewayInterface
{
    public function bulkInsert(string $model, array $documents): int;

    public function bulkUpdate(string $model, array $documents): int;

    public function bulkUpsert(string $model, array $documents): int;

    public function insert(string $model, array $record): int;

    public function delete(string $model, array $conditions): int;

    public function update(string $model, array $fieldValues, array $conditions): int;
}