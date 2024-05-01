<?php
declare(strict_types=1);

namespace ManaPHP\Mongodb;

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
use ManaPHP\Mongodb\Model\InferenceInterface;
use MongoDB\BSON\ObjectId;
use function is_bool;
use function is_float;
use function is_int;
use function is_object;
use function is_scalar;
use function is_string;
use function strlen;

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
        return Container::get(InferenceInterface::class)->fieldTypes(static::class);
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
            throw new MisuseException(['`{1}` model is not supported `{2}` type', static::class, $type]);
        }
    }

    /**
     * @return Query <static>
     */
    public static function newQuery(): Query
    {
        return Container::make('ManaPHP\Mongodb\Query')->setModel(static::class);
    }

    /**
     * @return static
     */
    public function create(): static
    {
        $this->autoFillCreated();

        $models = Container::get(ModelsInterface::class);

        $fields = $models->getFields(static::class);

        $this->validate($models->getFillable(static::class));

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

            if (isset($this->$field)) {
                if (is_scalar($this->$field)) {
                    $this->$field = $this->normalizeValue($type, $this->$field);
                }
            } else {
                $this->$field = $allowNull ? null : $this->normalizeValue($type, '');
            }
        }

        list($connection, $collection) = Container::get(ShardingInterface::class)->getUniqueShard(static::class, $this);

        $this->fireEvent(new ModelSaving($this));
        $this->fireEvent(new ModelCreating($this));

        $fieldValues = [];
        foreach ($fields as $field) {
            $fieldValues[$field] = $this->$field;
        }

        $fieldValues['_id'] = $this->_id;

        $mongodb = Container::get(MongodbConnectorInterface::class)->get($connection);
        $mongodb->insert($collection, $fieldValues);

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

        if (($changedFields = $this->getChangedFields()) === []) {
            return $this;
        }

        $this->validate($changedFields);

        $this->autoFillUpdated();

        list($connection, $collection) = Container::get(ShardingInterface::class)->getUniqueShard(static::class, $this);

        $this->fireEvent(new ModelSaving($this));
        $this->fireEvent(new ModelUpdating($this));

        $fieldValues = [];
        foreach ($models->getFields(static::class) as $field) {
            if (!isset($this->$field)) {
                if (isset($snapshot[$field])) {
                    $fieldValues[$field] = null;
                }
            } elseif (!isset($snapshot[$field]) || $snapshot[$field] !== $this->$field) {
                $fieldValues[$field] = $this->$field;
            }
        }

        $mongodb = Container::get(MongodbConnectorInterface::class)->get($connection);
        $mongodb->update($collection, $fieldValues, [$primaryKey => $this->$primaryKey]);

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

        $mongodb = Container::get(MongodbConnectorInterface::class)->get($connection);

        $mongodb->delete($table, [$primaryKey => $this->$primaryKey]);

        $this->fireEvent(new ModelDeleted($this));

        return $this;
    }

    public static function aggregateEx(array $pipeline, array $options = []): array
    {
        list($connection, $collection) = Container::get(ShardingInterface::class)->getUniqueShard(static::class, []);

        return Container::get(MongodbConnectorInterface::class)->get($connection)->aggregate(
            $collection, $pipeline, $options
        );
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
        if (isset($data['_id']) && is_object($data['_id'])) {
            $data['_id'] = (string)$data['_id'];
        }

        return $data;
    }
}