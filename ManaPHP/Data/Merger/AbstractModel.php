<?php

namespace ManaPHP\Data\Merger;

use ManaPHP\Data\QueryInterface;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Data\ModelInterface;

abstract class AbstractModel extends \ManaPHP\Data\AbstractModel
{
    abstract public function getModel(): ModelInterface;

    abstract public function getQueries(): array;

    public function newQuery(): QueryInterface
    {
        $queries = $this->getQueries();
        if (is_array($queries)) {
            $queries = $this->getNew('ManaPHP\Data\Merger\Query', [$queries]);
        }

        return $queries->setModel($this->getModel())->select($this->fields());
    }

    public function db(): string
    {
        throw new NotSupportedException(__METHOD__);
    }

    public static function connection(mixed $context = null): void
    {
        throw new NotSupportedException(__METHOD__);
    }

    /**
     * @return string =model_field(new static)
     */
    public function primaryKey(): string
    {
        return $this->getModel()->primaryKey();
    }

    /**
     * @return array =model_fields(new static)
     */
    public function fields(): array
    {
        static $cached = [];

        $class = static::class;
        if (!isset($cached[$class])) {
            $fields = [];
            foreach (get_class_vars($class) as $field => $value) {
                if ($value === null && $field[0] !== '_') {
                    $fields[] = $field;
                }
            }

            $cached[$class] = $fields ?: $this->getModel()->fields();
        }

        return $cached[$class];
    }

    /**
     * @return array =model_fields(new static)
     */
    public function intFields(): array
    {
        return $this->getModel()->intFields();
    }

    public function getNextAutoIncrementId(int $step = 1): int
    {
        throw new NotSupportedException(__METHOD__);
    }

    public function create(): static
    {
        throw new NotSupportedException(__METHOD__);
    }

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