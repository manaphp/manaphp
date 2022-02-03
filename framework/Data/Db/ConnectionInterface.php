<?php
declare(strict_types=1);

namespace ManaPHP\Data\Db;

interface ConnectionInterface
{
    public function getUri(): string;

    public function execute(string $sql, array $bind = [], bool $has_insert_id = false): int;

    public function query(string $sql, array $bind, int $mode): array;

    public function getMetadata(string $table): array;

    public function begin(): void;

    public function commit(): void;

    public function rollback(): void;

    public function truncate(string $table): void;

    public function drop(string $table): void;

    public function getTables(?string $schema = null): array;

    public function tableExists(string $table): bool;

    public function buildSql(array $params): string;

    public function replaceQuoteCharacters(string $sql): string;
}