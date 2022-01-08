<?php
declare(strict_types=1);

namespace ManaPHP\Data\Mongodb;

interface ConnectionInterface
{
    public function insert(string $namespace, array $document): int;

    public function bulkInsert(string $namespace, array $documents): int;

    public function update(string $source, array $document, array $filter): int;

    public function bulkUpdate(string $source, array $documents, string $primaryKey): int;

    public function upsert(string $namespace, array $document, string $primaryKey): int;

    public function bulkUpsert(string $namespace, array $documents, string $primaryKey): int;

    public function delete(string $namespace, array $filter): int;

    public function fetchAll(string $namespace, array $filter = [], array $options = [], bool $secondaryPreferred = true
    ): array;

    public function command(array $command, string $db): array;
}