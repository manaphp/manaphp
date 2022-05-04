<?php
declare(strict_types=1);

namespace ManaPHP\Data\Mongodb;

use ManaPHP\Data\AbstractModel;
use ManaPHP\Data\Model\ShardingInterface;
use ManaPHP\Data\Mongodb\Model\InferrerInterface;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Helper\Container;
use MongoDB\BSON\ObjectId;

class Model extends AbstractModel
{
    protected static bool $_defaultAllowNullValue = false;
    public mixed $_id;

    public static function setDefaultAllowNullValue(bool $allow): void
    {
        self::$_defaultAllowNullValue = $allow;
    }

    /**
     * bool, int, float, string, array, objectid
     *
     * @return array =model_var(new static)
     */
    public function fieldTypes(): array
    {
        return Container::get(InferrerInterface::class)->fieldTypes(static::class);
    }

    public function isAllowNullValue(): bool
    {
        return self::$_defaultAllowNullValue;
    }

    public function normalizeValue(string $type, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($type === 'string') {
            return is_string($value) ? $value : (string)$value;
        } elseif ($type === 'int') {
            return is_int($value) ? $value : (int)$value;
        } elseif ($type === 'float') {
            return is_float($value) ? $value : (float)$value;
        } elseif ($type === 'objectid') {
            return is_scalar($value) ? new ObjectID($value) : $value;
        } elseif ($type === 'bool') {
            return is_bool($value) ? $value : (bool)$value;
        } elseif ($type === 'array') {
            return (array)$value;
        } else {
            throw new MisuseException(['`%s` model is not supported `%s` type', static::class, $type]);
        }
    }

    /**
     * @return \ManaPHP\Data\Mongodb\Query <static>
     */
    public function newQuery(): Query
    {
        return Container::make('ManaPHP\Data\Mongodb\Query')->setModel($this);
    }

    public function create(): static
    {
        $fields = $this->_modelManager->getFields(static::class);
        foreach ($this->getAutoCreatedData() as $field => $value) {
            if ($this->$field === null) {
                $this->$field = $value;
            }
        }

        $this->validate($fields);

        if ($this->_id) {
            if (is_string($this->_id) && strlen($this->_id) === 24) {
                $this->_id = new ObjectID($this->_id);
            }
        } else {
            $this->_id = new ObjectID();
        }

        $allowNull = $this->isAllowNullValue();
        foreach ($this->fieldTypes() as $field => $type) {
            if ($field === '_id') {
                continue;
            }

            if ($this->$field !== null) {
                if (is_scalar($this->$field)) {
                    $this->$field = $this->normalizeValue($type, $this->$field);
                }
            } else {
                $this->$field = $allowNull ? null : $this->normalizeValue($type, '');
            }
        }

        list($connection, $collection) = Container::get(ShardingInterface::class)->getUniqueShard(static::class, $this);

        $this->fireEvent('model:saving');
        $this->fireEvent('model:creating');

        $fieldValues = [];
        foreach ($fields as $field) {
            $fieldValues[$field] = $this->$field;
        }

        $fieldValues['_id'] = $this->_id;

        $mongodb = Container::get(FactoryInterface::class)->get($connection);
        $mongodb->insert($collection, $fieldValues);

        $this->fireEvent('model:created');
        $this->fireEvent('model:saved');

        $this->_snapshot = $this->toArray();

        return $this;
    }

    public function update(): static
    {
        $primaryKey = $this->_modelManager->getPrimaryKey(static::class);

        if ($this->$primaryKey === null) {
            throw new MisuseException('missing primary key value');
        }

        $this->_snapshot[$primaryKey] ??= $this->$primaryKey;

        $snapshot = $this->_snapshot;

        /** @noinspection TypeUnsafeComparisonInspection */
        if ($this->$primaryKey != $snapshot[$primaryKey]) {
            throw new MisuseException('updating model primary key value is not support');
        }

        $fieldTypes = $this->fieldTypes();
        $fields = $this->_modelManager->getFields(static::class);

        foreach ($fields as $field) {
            if ($this->$field === null) {
                null;
            } elseif (!isset($snapshot[$field])) {
                if (is_scalar($this->$field)) {
                    $this->$field = $this->normalizeValue($fieldTypes[$field], $this->$field);
                }
            } elseif ($snapshot[$field] !== $this->$field) {
                if (is_scalar($this->$field)) {
                    $this->$field = $this->normalizeValue($fieldTypes[$field], $this->$field);
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

        list($connection, $collection) = Container::get(ShardingInterface::class)->getUniqueShard(static::class, $this);

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

        $mongodb = Container::get(FactoryInterface::class)->get($connection);
        $mongodb->update($collection, $fieldValues, [$primaryKey => $this->$primaryKey]);

        $this->fireEvent('model:updated');
        $this->fireEvent('model:saved');

        $this->_snapshot = $this->toArray();

        return $this;
    }

    public function delete(): static
    {
        $primaryKey = $this->_modelManager->getPrimaryKey(static::class);

        if ($this->$primaryKey === null) {
            throw new MisuseException('missing primary key value');
        }

        list($connection, $table) = Container::get(ShardingInterface::class)->getUniqueShard(static::class, $this);

        $this->fireEvent('model:deleting');

        $mongodb = Container::get(FactoryInterface::class)->get($connection);

        $mongodb->delete($table, [$primaryKey => $this->$primaryKey]);

        $this->fireEvent('model:deleted');

        return $this;
    }

    public static function aggregateEx(array $pipeline, array $options = []): array
    {
        list($connection, $collection) = Container::get(ShardingInterface::class)->getUniqueShard(static::class, []);

        return Container::get(FactoryInterface::class)->get($connection)->aggregate($collection, $pipeline, $options);
    }

    public function normalizeDocument(array $document): array
    {
        $allowNull = $this->isAllowNullValue();
        $fieldTypes = $this->fieldTypes();

        foreach ($fieldTypes as $field => $type) {
            if (isset($document[$field])) {
                $document[$field] = $this->normalizeValue($type, $document[$field]);
            } elseif ($field !== '_id') {
                $document[$field] = $allowNull ? null : $this->normalizeValue($type, '');
            }
        }

        return $document;
    }

    public function __debugInfo()
    {
        $data = parent::__debugInfo();
        if ($data['_id'] === null) {
            unset($data['_id']);
        } elseif (is_object($data['_id'])) {
            $data['_id'] = (string)$data['_id'];
        }

        return $data;
    }
}