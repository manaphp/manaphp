<?php
declare(strict_types=1);

namespace ManaPHP\Data\Merger;

use ManaPHP\Data\ModelInterface;
use ManaPHP\Data\QueryInterface;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Helper\Container;

abstract class AbstractModel extends \ManaPHP\Data\AbstractModel
{
    abstract public function getModel(): ModelInterface;

    abstract public function getQueries(): array;

    public function newQuery(): QueryInterface
    {
        $queries = Container::make('ManaPHP\Data\Merger\Query', [$this->getQueries()]);
        return $queries->setModel($this->getModel())->select($this->_modelManager->getFields(static::class));
    }

    public function create(): static
    {
        throw new NotSupportedException(__METHOD__);
    }

    /** @noinspection PhpUnusedParameterInspection */
    public static function insert(array $record): int
    {
        throw new NotSupportedException(__METHOD__);
    }

    public function update(): static
    {
        throw new NotSupportedException(__METHOD__);
    }

    public function delete(): static
    {
        throw new NotSupportedException(__METHOD__);
    }
}