<?php
declare(strict_types=1);

namespace ManaPHP\Persistence;

interface ShardingInterface
{
    public function getAnyShard(string $entityClass): array;

    public function getUniqueShard(string $entityClass, array|Entity $context): array;

    public function getMultipleShards(string $entityClass, array|Entity $context): array;

    public function getAllShards(string $entityClass): array;
}