<?php
declare(strict_types=1);

namespace ManaPHP\Data;

use ManaPHP\Data\Db\Query;
use ManaPHP\Pool\Transientable;
use PDO;

interface DbInterface extends Transientable
{
    public function getPrefix(): string;

    public function execute(string $type, string $sql, array $bind = []): int;

    public function executeInsert(string $sql, array $bind = []): int;

    public function executeUpdate(string $sql, array $bind = []): int;

    public function executeDelete(string $sql, array $bind = []): int;

    public function fetchOne(string $sql, array $bind = [], int $mode = PDO::FETCH_ASSOC, bool $useMaster = false
    ): false|array;

    public function fetchAll(string $sql, array $bind = [], int $mode = PDO::FETCH_ASSOC, bool $useMaster = false
    ): array;

    public function insert(string $table, array $record, bool $fetchInsertId = false): mixed;

    public function insertBySql(string $table, string $sql, array $bind = []): int;

    public function update(string $table, array $fieldValues, string|array $conditions, array $bind = []): int;

    public function updateBySql(string $table, string $sql, array $bind = []): int;

    public function upsert(string $table, array $insertFieldValues, array $updateFieldValues = [],
        ?string $primaryKey = null
    ): int;

    public function delete(string $table, string|array $conditions, array $bind = []): int;

    public function deleteBySql(string $table, string $sql, array $bind = []): int;

    public function getSQL(): string;

    public function getBind(): array;

    public function affectedRows(): int;

    public function begin(): void;

    public function isUnderTransaction(): bool;

    public function rollback(): void;

    public function commit(): void;

    public function getMetadata(string $table): array;

    public function getTables(?string $schema = null): array;

    public function buildSql(array $params): string;

    public function getLastSql(): string;

    public function query(?string $table = null, ?string $alias = null): Query;
}