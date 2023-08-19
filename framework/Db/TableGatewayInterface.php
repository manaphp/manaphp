<?php
declare(strict_types=1);

namespace ManaPHP\Db;

interface TableGatewayInterface
{
    public function insert(string $model, array $record, bool $fetchInsertId = false): mixed;

    public function insertBySql(string $model, string $sql, array $bind = []): int;

    public function delete(string $model, string|array $conditions, array $bind = []): int;

    public function deleteBySql(string $model, string $sql, array $bind = []): int;

    public function update(string $model, array $fieldValues, string|array $conditions, array $bind = []): int;

    public function updateBySql(string $model, string $sql, array $bind = []): int;
}