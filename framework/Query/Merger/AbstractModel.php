<?php
declare(strict_types=1);

namespace ManaPHP\Query\Merger;

use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Helper\Container;
use ManaPHP\Query\QueryInterface;

abstract class AbstractModel extends \ManaPHP\Model\AbstractModel
{
    protected static function newQueryInternal(array $queries): QueryInterface
    {
        $queries = Container::make('ManaPHP\Query\Merger\Query', [$queries]);
        return $queries->setModel($queries[0]->getModel());
    }

    public function create(array $kv = []): static
    {
        throw new NotSupportedException(__METHOD__);
    }

    /** @noinspection PhpUnusedParameterInspection */
    public static function insert(array $record): int
    {
        throw new NotSupportedException(__METHOD__);
    }

    public function update(array $kv = []): static
    {
        throw new NotSupportedException(__METHOD__);
    }

    public function delete(): static
    {
        throw new NotSupportedException(__METHOD__);
    }
}