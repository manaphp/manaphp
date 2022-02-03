<?php
declare(strict_types=1);

namespace ManaPHP\Data;

interface TableInterface
{
    public function table(): string;

    public function db(): string;

    public function getAnyShard(): array;

    public function getUniqueShard(array|ModelInterface $context): array;

    public function getMultipleShards(array|ModelInterface $context): array;

    public function getAllShards(): array;

    public static function sample(): static;
}