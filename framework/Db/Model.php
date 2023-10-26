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
use ManaPHP\Model\ModelsInterface;
use ManaPHP\Model\ShardingInterface;

class Model extends AbstractModel implements ModelInterface
{
    /**
     * @return Query <static>
     */
    public static function newQuery(): Query
    {
        return Container::make('ManaPHP\Db\Query')->setModel(static::class);
    }

    /**
     * @return static
     */
    public function create(): static
    {
        $this->autoFillCreated();

        $models = Container::get(ModelsInterface::class);

        $fields = $models->getFields(static::class);

        $this->validate($fields);

        $primaryKey = $models->getPrimaryKey(static::class);

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

        foreach ($models->getColumnMap(static::class) as $propery => $column) {
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
            $query = static::newQuery()->select($defaultValueFields)->where([$primaryKey => $this->$primaryKey]);
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
        $models = Container::get(ModelsInterface::class);

        $primaryKey = $models->getPrimaryKey(static::class);

        if (!isset($this->$primaryKey)) {
            throw new MisuseException('missing primary key value');
        }

        $this->_snapshot[$primaryKey] ??= $this->$primaryKey;

        $snapshot = $this->_snapshot;

        /** @noinspection TypeUnsafeComparisonInspection */
        if ($this->$primaryKey != $snapshot[$primaryKey]) {
            throw new MisuseException('updating model primary key value is not support');
        }

        $fields = $models->getFields(static::class);

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

        $columnMap = $models->getColumnMap(static::class);
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
        $primaryKey = Container::get(ModelsInterface::class)->getPrimaryKey(static::class);

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
