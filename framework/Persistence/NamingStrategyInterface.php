<?php
declare(strict_types=1);

namespace ManaPHP\Persistence;

interface NamingStrategyInterface
{
    public function classToTableName($className): string;

    public function propertyToColumnName($propertyName, $className = null): string;
}