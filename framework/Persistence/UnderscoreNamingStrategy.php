<?php

declare(strict_types=1);

namespace ManaPHP\Persistence;

use function preg_replace;
use function strrpos;
use function strtolower;
use function substr;

class UnderscoreNamingStrategy implements NamingStrategyInterface
{
    protected function underscore(string $str): string
    {
        return strtolower(preg_replace('/(?<=[a-z0-9])([A-Z])/', '_$1', $str));
    }

    public function classToTableName($className): string
    {
        if (str_contains($className, '\\')) {
            return $this->underscore(substr($className, strrpos($className, '\\') + 1));
        } else {
            return $this->underscore($className);
        }
    }

    public function propertyToColumnName($propertyName, $className = null): string
    {
        return $this->underscore($propertyName);
    }
}