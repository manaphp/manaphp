<?php
declare(strict_types=1);

namespace ManaPHP\Db;

use ManaPHP\Exception\MisuseException;
use ManaPHP\Helper\Container;
use ManaPHP\Model\AbstractModel;
use ManaPHP\Model\Event\ModelCreated;
use ManaPHP\Model\Event\ModelCreating;
use ManaPHP\Model\Event\ModelDeleted;
use ManaPHP\Model\Event\ModelDeleting;
use ManaPHP\Model\Event\ModelSaved;
use ManaPHP\Model\Event\ModelSaving;
use ManaPHP\Model\Event\ModelUpdated;
use ManaPHP\Model\Event\ModelUpdating;
use ManaPHP\Model\ModelManagerInterface;
use ManaPHP\Model\ShardingInterface;

class Model extends AbstractModel implements ModelInterface
{
    /**
     * @return Query <static>
     */
    public function newQuery(): Query
    {
        return Container::make('ManaPHP\Db\Query')->setModel(static::class);
    }

    /**
     * @return static
     */
    public function create(): static
    {
        $this->autoFillCreated();

        $modelManager = Container::get(ModelManagerInterface::class);

        $fields = $modelManager->getFields(static::class);

        $this->validate($fields);

        $primaryKey = $modelManager->getPrimaryKey(static::class);

        list($connection, $table) = Container::get(ShardingInterface::class)->getUniqueShard(static::class, $this);

        $this->fireEvent(new ModelSaving($this));
        $this->fireEvent(new ModelCreating($this));

        $fieldValues = [];
        $defaultValueFields = [];
        foreach ($fields as $field) {
            if (isset($this->$field)) {
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
        if (!isset($this->$primaryKey)) {
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

        $this->fireEvent(new ModelCreated($this));
        $this->fireEvent(new ModelSaved($this));

        $this->_snapshot = $this->toArray();

        return $this;
    }

    /**
     * @return static
     */
    public function update(): static
    {
        $modelManager = Container::get(ModelManagerInterface::class);

        $primaryKey = $modelManager->getPrimaryKey(static::class);

        if (!isset($this->$primaryKey)) {
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
            if (!isset($this->$field)) {
                null;
            } elseif (!isset($snapshot[$field])) {
                null;
            } elseif ($snapshot[$field] !== $this->$field) {
                /** @noinspection PhpConditionCheckedByNextConditionInspection */
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

        $this->autoFillUpdated();

        list($connection, $table) = Container::get(ShardingInterface::class)->getUniqueShard(static::class, $this);

        $this->fireEvent(new ModelSaving($this));
        $this->fireEvent(new ModelUpdating($this));

        $fieldValues = [];
        foreach ($fields as $field) {
            if (!isset($this->$field)) {
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

        $this->fireEvent(new ModelUpdated($this));
        $this->fireEvent(new ModelSaved($this));

        $this->_snapshot = $this->toArray();

        return $this;
    }

    public function delete(): static
    {
        $primaryKey = Container::get(ModelManagerInterface::class)->getPrimaryKey(static::class);

        if (!isset($this->$primaryKey)) {
            throw new MisuseException('missing primary key value');
        }

        list($connection, $table) = Container::get(ShardingInterface::class)->getUniqueShard(static::class, $this);

        $this->fireEvent(new ModelDeleting($this));

        $db = Container::get(DbConnectorInterface::class)->get($connection);

        $db->delete($table, [$primaryKey => $this->$primaryKey]);

        $this->fireEvent(new ModelDeleted($this));

        return $this;
    }
}
