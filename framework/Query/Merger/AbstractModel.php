<?php
declare(strict_types=1);

namespace ManaPHP\Data\Merger;

use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Helper\Container;
use ManaPHP\Model\ModelManagerInterface;
use ManaPHP\Query\QueryInterface;

abstract class AbstractModel extends \ManaPHP\Model\AbstractModel
{
    abstract public function getModel(): string;

    abstract public function getQueries(): array;

    public function newQuery(): QueryInterface
    {
        $modelManager = Container::get(ModelManagerInterface::class);

        $queries = Container::make('ManaPHP\Data\Merger\Query', [$this->getQueries()]);
        return $queries->setModel($this->getModel())->select($modelManager->getFields(static::class));
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