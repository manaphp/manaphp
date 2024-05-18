<?php
declare(strict_types=1);

namespace ManaPHP\Persistence;

interface EntityMetadataInterface
{
    public function getTable(string $entityClass): string;

    public function getConnection(string $entityClass): string;

    public function getPrimaryKey(string $entityClass): string;

    public function getReferencedKey(string $entityClass): string;

    public function getFields(string $entityClass): array;

    public function getColumnMap(string $entityClass): array;

    public function getFillable(string $entityClass): array;

    public function getDateFormat(string $entityClass): string;

    public function getRepository(string $entityClass): RepositoryInterface;

    public function getNamingStrategy(string $entityClass): NamingStrategyInterface;

    public function getConstraints(string $entityClass): array;
}