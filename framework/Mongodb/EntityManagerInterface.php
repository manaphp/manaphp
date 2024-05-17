<?php
declare(strict_types=1);

namespace ManaPHP\Mongodb;

use ManaPHP\Query\QueryInterface;

interface EntityManagerInterface extends \ManaPHP\Persistence\EntityManagerInterface
{
    public function query(string $entityClass): QueryInterface;

    public function normalizeDocument(string $entityClass, array $document): array;
}