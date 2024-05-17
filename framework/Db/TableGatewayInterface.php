<?php
declare(strict_types=1);

namespace ManaPHP\Db;

interface TableGatewayInterface
{
    public function insert(string $entityClass, array $record, bool $fetchInsertId = false): mixed;

    public function insertBySql(string $entityClass, string $sql, array $bind = []): int;

    public function delete(string $entityClass, string|array $conditions, array $bind = []): int;

    public function deleteBySql(string $entityClass, string $sql, array $bind = []): int;

    public function update(string $entityClass, array $fieldValues, string|array $conditions, array $bind = []): int;

    public function updateBySql(string $entityClass, string $sql, array $bind = []): int;
}