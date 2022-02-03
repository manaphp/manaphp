<?php
declare(strict_types=1);

namespace ManaPHP\Data;

use ManaPHP\Data\Mongodb\Query;

interface MongodbInterface
{
    public function getPrefix(): string;

    public function getDb(): string;

    public function insert(string $source, array $document): int;

    public function bulkInsert(string $source, array $documents): int;

    public function update(string $source, array $document, array $filter): int;

    public function bulkUpdate(string $source, array $documents, string $primaryKey): int;

    public function upsert(string $source, array $document, string $primaryKey): int;

    public function bulkUpsert(string $source, array $documents, string $primaryKey): int;

    public function delete(string $source, array $filter): int;

    public function fetchAll(string $source, array $filter = [], array $options = [], bool $secondaryPreferred = true
    ): array;

    public function command(array $command, ?string $db = null): array;

    public function aggregate(string $source, array $pipeline, array $options = []): array;

    public function truncate(string $source): bool;

    public function listDatabases(): array;

    public function listCollections(?string $db = null): array;

    public function query(?string $collection = null): Query;
}