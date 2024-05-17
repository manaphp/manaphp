<?php
declare(strict_types=1);

namespace ManaPHP\Mongodb;

interface CollectionGatewayInterface
{
    public function bulkInsert(string $entityClass, array $documents): int;

    public function bulkUpdate(string $entityClass, array $documents): int;

    public function bulkUpsert(string $entityClass, array $documents): int;

    public function insert(string $entityClass, array $record): int;

    public function delete(string $entityClass, array $conditions): int;

    public function update(string $entityClass, array $fieldValues, array $conditions): int;
}