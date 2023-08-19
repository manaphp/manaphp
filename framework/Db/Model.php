<?php
declare(strict_types=1);

namespace ManaPHP\Db;

use ManaPHP\Exception\MisuseException;
use ManaPHP\Helper\Container;
use ManaPHP\Model\AbstractModel;
use ManaPHP\Model\ModelManagerInterface;
use ManaPHP\Model\ShardingInterface;

class Model extends AbstractModel implements ModelInterface
{
    /**
     * @return \ManaPHP\Db\Query <static>
     */
    public function newQuery(): Query
    {
        return Container::make('ManaPHP\Db\Query')->setModel(static::class);
    }

    public function create(): static
    {
        $modelManager = Container::get(ModelManagerInterface::class);

        $fields = $modelManager->getFields(static::class);
        foreach ($this->getAutoCreatedData() as $field => $value) {
            if ($this->$field === null) {
                $this->$field = $value;
            }
        }

        $this->validate($fields);

        $primaryKey = $modelManager->getPrimaryKey(static::class);

        list($connection, $table) = Container::get(ShardingInterface::class)->getUniqueShard(static::class, $this);

        $this->fireEvent('model:saving');
        $this->fireEvent('model:creating');

        $fieldValues = [];
        $defaultValueFields = [];
        foreach ($fields as $field) {
            if ($this->$field !== null) {
                $fieldValues[$field] = $this->$field;
            } elseif ($field !== $primaryKey) {
                $defaultValueFields[] = $field;
            }
        }

        foreach ($modelManager->getColumnMap(static::class) as $propery => $column) {
            if (array_key_exists($propery, $fieldValues)) {
                $fieldValues[$column] = $fieldValues[$propery];
                unset($fieldValues[$propery]);
            }
        }

        $db = Container::get(DbConnectorInterface::class)->get($connection);
        if ($this->$primaryKey === null) {
            $this->$primaryKey = (int)$db->insert($table, $fieldValues, true);
        } else {
            $db->insert($table, $fieldValues);
        }

        if ($defaultValueFields) {
            $query = $this->newQuery()->select($defaultValueFields)->where([$primaryKey => $this->$primaryKey]);
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
        $modelManager = Container::get(ModelManagerInterface::class);

        $primaryKey = $modelManager->getPrimaryKey(static::class);

        if ($this->$primaryKey === null) {
            throw new MisuseException('missing primary key value');
        }

        $this->_snapshot[$primaryKey] ??= $this->$primaryKey;

        $snapshot = $this->_snapshot;

        /** @noinspection TypeUnsafeComparisonInspection */
        if ($this->$primaryKey != $snapshot[$primaryKey]) {
            throw new MisuseException('updating model primary key value is not support');
        }

        $fields = $modelManager->getFields(static::class);

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

        list($connection, $table) = Container::get(ShardingInterface::class)->getUniqueShard(static::class, $this);

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

        $columnMap = $modelManager->getColumnMap(static::class);
        foreach ($columnMap as $property => $column) {
            if (array_key_exists($property, $fieldValues)) {
                $fieldValues[$column] = $fieldValues[$property];
                unset($fieldValues[$property]);
            }
        }

        $db = Container::get(DbConnectorInterface::class)->get($connection);
        $db->update($table, $fieldValues, [$columnMap[$primaryKey] ?? $primaryKey => $this->$primaryKey]);

        $this->fireEvent('model:updated');
        $this->fireEvent('model:saved');

        $this->_snapshot = $this->toArray();

        return $this;
    }

    public function delete(): static
    {
        $primaryKey = Container::get(ModelManagerInterface::class)->getPrimaryKey(static::class);

        if ($this->$primaryKey === null) {
            throw new MisuseException('missing primary key value');
        }

        list($connection, $table) = Container::get(ShardingInterface::class)->getUniqueShard(static::class, $this);

        $this->fireEvent('model:deleting');

        $db = Container::get(DbConnectorInterface::class)->get($connection);

        $db->delete($table, [$primaryKey => $this->$primaryKey]);

        $this->fireEvent('model:deleted');

        return $this;
    }
}
