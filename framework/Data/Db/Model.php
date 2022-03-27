<?php
declare(strict_types=1);

namespace ManaPHP\Data\Db;

use ManaPHP\Data\AbstractModel;
use ManaPHP\Data\Db\Model\MetadataInterface;
use ManaPHP\Data\Model\ExpressionInterface;
use ManaPHP\Data\Model\ThoseInterface;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Helper\Container;
use ManaPHP\Logging\LoggerInterface;

class Model extends AbstractModel implements ModelInterface
{
    public function connection(): string
    {
        return 'default';
    }

    public function getModelMetadata(): MetadataInterface
    {
        return Container::get(MetadataInterface::class);
    }

    /**
     * @return string =model_field(new static)
     */
    public function primaryKey(): string
    {
        static $cached = [];

        $class = static::class;

        if (!isset($cached[$class])) {
            if ($primaryKey = $this->inferPrimaryKey($class)) {
                return $cached[$class] = $primaryKey;
            } else {
                $primaryKeys = $this->getModelMetadata()->getPrimaryKeyAttributes($this);
                if (count($primaryKeys) !== 1) {
                    throw new NotSupportedException('only support one primary key');
                }
                $primaryKey = $primaryKeys[0];
                return $cached[$class] = array_search($primaryKey, $this->mapFields(), true) ?: $primaryKey;
            }
        }

        return $cached[$class];
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
            $cached[$class] = $fields ?: $this->getModelMetadata()->getAttributes($this);
        }

        return $cached[$class];
    }

    /**
     * @return array =model_fields(new static)
     */
    public function intFields(): array
    {
        static $cached;

        $class = static::class;
        if (($fields = $cached[$class] ?? null) === null) {
            if ($mapFields = $this->mapFields()) {
                foreach ($this->getModelMetadata()->getIntTypeAttributes($this) as $field) {
                    $fields[] = array_search($field, $mapFields, true) ?: $field;
                }
            } else {
                $fields = $this->getModelMetadata()->getIntTypeAttributes($this);
            }

            $cached[$class] = $fields;
        }

        return $fields;
    }

    public function getNextAutoIncrementId(int $step = 1): ?int
    {
        return null;
    }

    /**
     * @return \ManaPHP\Data\Db\Query <static>
     */
    public function newQuery(): Query
    {
        return Container::make('ManaPHP\Data\Db\Query')->setModel($this);
    }

    public function create(): static
    {
        $autoIncrementField = $this->autoIncrementField();
        if ($autoIncrementField && $this->$autoIncrementField === null) {
            $this->$autoIncrementField = $this->getNextAutoIncrementId();
        }

        $fields = $this->fields();
        foreach ($this->getAutoCreatedData() as $field => $value) {
            if ($this->$field === null) {
                $this->$field = $value;
            }
        }

        $this->validate($fields);

        list($connection, $table) = $this->getUniqueShard($this);

        $this->fireEvent('model:saving');
        $this->fireEvent('model:creating');

        $fieldValues = [];
        $defaultValueFields = [];
        foreach ($fields as $field) {
            if ($this->$field !== null) {
                $fieldValues[$field] = $this->$field;
            } elseif ($field !== $autoIncrementField) {
                $defaultValueFields[] = $field;
            }
        }

        foreach ($this->jsonFields() as $field) {
            if (is_array($this->$field)) {
                $fieldValues[$field] = json_stringify($this->$field);
            }
        }

        foreach ($this->mapFields() as $propery => $column) {
            if (array_key_exists($propery, $fieldValues)) {
                $fieldValues[$column] = $fieldValues[$propery];
                unset($fieldValues[$propery]);
            }
        }

        $db = Container::get(FactoryInterface::class)->get($connection);
        if ($autoIncrementField && $this->$autoIncrementField === null) {
            $this->$autoIncrementField = (int)$db->insert($table, $fieldValues, true);
        } else {
            $db->insert($table, $fieldValues);
        }

        if ($defaultValueFields) {
            $primaryKey = $this->primaryKey();
            $query = $this->newQuery()->select($defaultValueFields)->whereEq($primaryKey, $this->$primaryKey);
            if ($r = $query->execute()) {
                foreach ($r[0] as $field => $value) {
                    $this->$field = $value;
                }
            }
        }

        $this->fireEvent('model:created');
        $this->fireEvent('model:saved');

        $this->_snapshot = $this->toArray();

        return $this;
    }

    public function update(): static
    {
        $primaryKey = $this->primaryKey();

        if ($this->$primaryKey === null) {
            throw new MisuseException('missing primary key value');
        }

        if (!isset($snapshot[$primaryKey])) {
            $this->_snapshot[$primaryKey] = $this->$primaryKey;
        }

        $snapshot = $this->_snapshot;

        /** @noinspection TypeUnsafeComparisonInspection */
        if ($this->$primaryKey != $snapshot[$primaryKey]) {
            throw new MisuseException('updating model primary key value is not support');
        }

        $fields = $this->fields();

        foreach ($fields as $field) {
            if ($this->$field === null) {
                null;
            } elseif (!isset($snapshot[$field])) {
                null;
            } elseif ($snapshot[$field] !== $this->$field) {
                if ((is_string($this->$field) && !is_string($snapshot[$field]))
                    && (string)$snapshot[$field] === $this->$field
                ) {
                    $this->$field = $snapshot[$field];
                }
            }
        }

        $this->validate();

        if (!$this->hasChanged($fields)) {
            return $this;
        }

        foreach ($this->getAutoUpdatedData() as $field => $value) {
            $this->$field = $value;
        }

        list($connection, $table) = $this->getUniqueShard($this);

        $this->fireEvent('model:saving');
        $this->fireEvent('model:updating');

        $fieldValues = [];
        foreach ($fields as $field) {
            if ($this->$field === null) {
                if (isset($snapshot[$field])) {
                    $fieldValues[$field] = null;
                }
            } elseif (!isset($snapshot[$field]) || $snapshot[$field] !== $this->$field) {
                $fieldValues[$field] = $this->$field;
            }
        }

        foreach ($this->jsonFields() as $field) {
            if (isset($fieldValues[$field]) && is_array($fieldValues[$field])) {
                $fieldValues[$field] = json_stringify($fieldValues[$field]);
            }
        }

        $bind = [];
        $expressionFields = [];
        foreach ($fieldValues as $field => $value) {
            if ($value instanceof ExpressionInterface) {
                $expressionFields[] = $field;
                $compiled = $value->compile($this, $field);

                $fieldValues[] = $compiled[0];
                if (count($compiled) !== 1) {
                    unset($compiled[0]);
                    $bind = $bind ? array_merge($bind, $compiled) : $compiled;
                }
                unset($fieldValues[$field]);
            }
        }

        $mapFields = $this->mapFields();
        foreach ($mapFields as $property => $column) {
            if (array_key_exists($property, $fieldValues)) {
                $fieldValues[$column] = $fieldValues[$property];
                unset($fieldValues[$property]);
            }
        }

        $db = Container::get(FactoryInterface::class)->get($connection);
        $db->update(
            $table, $fieldValues, [$mapFields[$primaryKey] ?? $primaryKey => $this->$primaryKey], $bind
        );

        if ($expressionFields) {
            $query = $this->newQuery()->select($expressionFields)->whereEq($primaryKey, $this->$primaryKey);
            if ($rs = $query->execute()) {
                foreach ((array)$rs[0] as $field => $value) {
                    $this->$field = $value;
                }
            }
        }

        $this->fireEvent('model:updated');
        $this->fireEvent('model:saved');

        $this->_snapshot = $this->toArray();

        return $this;
    }

    public function delete(): static
    {
        $primaryKey = $this->primaryKey();

        if ($this->$primaryKey === null) {
            throw new MisuseException('missing primary key value');
        }

        list($connection, $table) = $this->getUniqueShard($this);

        $this->fireEvent('model:deleting');

        $db = Container::get(FactoryInterface::class)->get($connection);

        $db->delete($table, [$primaryKey => $this->$primaryKey]);

        $this->fireEvent('model:deleted');

        return $this;
    }

    public static function insertBySql(string $sql, array $bind = []): int
    {
        /** @noinspection OneTimeUseVariablesInspection */
        $that = Container::get(ThoseInterface::class)->get(static::class);

        list($connection, $table) = $that->getUniqueShard($bind);

        $db = Container::get(FactoryInterface::class)->get($connection);

        return $db->insertBySql($table, $sql, $bind);
    }

    public static function deleteBySql(string $sql, array $bind = []): int
    {
        $shards = Container::get(ThoseInterface::class)->get(static::class)->getMultipleShards($bind);

        $affected_count = 0;
        foreach ($shards as $connection => $tables) {
            $db = Container::get(FactoryInterface::class)->get($connection);

            foreach ($tables as $table) {
                $affected_count += $db->deleteBySql($table, $sql, $bind);
            }
        }

        return $affected_count;
    }

    public static function updateBySql(string $sql, array $bind = []): int
    {
        $shards = Container::get(ThoseInterface::class)->get(static::class)->getMultipleShards($bind);

        $affected_count = 0;
        foreach ($shards as $connection => $tables) {
            $db = Container::get(FactoryInterface::class)->get($connection);

            foreach ($tables as $table) {
                $affected_count += $db->updateBySql($table, $sql, $bind);
            }
        }

        return $affected_count;
    }

    /**
     * @param array $record =model_var(new static)
     *
     * @return int
     */
    public static function insert(array $record): int
    {
        $that = Container::get(ThoseInterface::class)->get(static::class);

        list($connection, $table) = $that->getUniqueShard($record);
        $logger = Container::get(LoggerInterface::class);

        if ($fields = array_diff(array_keys($record), $that->getModelMetadata()->getAttributes($that))) {
            $logger->debug(['insert `:1` table skip fields: :2', $table, array_values($fields)]);

            foreach ($fields as $field) {
                unset($record[$field]);
            }
        }

        $db = Container::get(FactoryInterface::class)->get($connection);
        $db->insert($table, $record);

        return 1;
    }
}
