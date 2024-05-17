<?php
declare(strict_types=1);

namespace ManaPHP\Db;

use ManaPHP\Query\QueryInterface;

interface EntityManagerInterface extends \ManaPHP\Persistence\EntityManagerInterface
{
    public function query(string $entityClass): QueryInterface;
}