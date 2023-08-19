<?php
declare(strict_types=1);

namespace ManaPHP\Model;

interface ShardingInterface
{
    public function getAnyShard(string $model): array;

    public function getUniqueShard(string $model, array|ModelInterface $context): array;

    public function getMultipleShards(string $model, array|ModelInterface $context): array;

    public function getAllShards(string $model): array;
}