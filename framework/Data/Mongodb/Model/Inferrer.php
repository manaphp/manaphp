<?php
declare(strict_types=1);

namespace ManaPHP\Data\Mongodb\Model;

use ManaPHP\Data\Model\ManagerInterface;
use ManaPHP\Data\Model\ShardingInterface;
use ManaPHP\Data\Model\ThoseInterface;
use ManaPHP\Data\Mongodb\ConnectorInterface;
use ManaPHP\Data\Mongodb\Model;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Exception\NotImplementedException;
use ManaPHP\Exception\RuntimeException;
use MongoDB\BSON\ObjectId;

class Inferrer implements InferrerInterface
{
    #[Inject] protected ThoseInterface $those;
    #[Inject] protected ConnectorInterface $connector;
    #[Inject] protected ShardingInterface $sharding;
    #[Inject] protected ManagerInterface $modelManager;

    protected array $primaryKey = [];
    protected array $fields = [];
    protected array $intFields = [];
    protected array $fieldTypes = [];

    protected function getThat(string $model): Model
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->those->get($model);
    }

    protected function primaryKeyInternal(string $model): ?string
    {
        $fields = $this->modelManager->getFields($model);

        if (in_array('id', $fields, true)) {
            return 'id';
        }

        $prefix = lcfirst(($pos = strrpos($model, '\\')) === false ? $model : substr($model, $pos + 1));
        if (in_array($tryField = $prefix . '_id', $fields, true)) {
            return $tryField;
        } elseif (in_array($tryField = $prefix . 'Id', $fields, true)) {
            return $tryField;
        }

        $table = $this->modelManager->getTable($model);
        if (($pos = strpos($table, ':')) !== false) {
            $table = substr($table, 0, $pos);
        } elseif (($pos = strpos($table, ',')) !== false) {
            $table = substr($table, 0, $pos);
        }

        $prefix = (($pos = strpos($table, '.')) ? substr($table, $pos + 1) : $table);
        if (in_array($tryField = $prefix . '_id', $fields, true)) {
            return $tryField;
        } elseif (in_array($tryField = $prefix . 'Id', $fields, true)) {
            return $tryField;
        }

        return null;
    }

    public function primaryKey(string $model): string
    {
        if (($primaryKey = $this->primaryKey[$model] ?? null) === null) {
            if ($primaryKey = $this->primaryKeyInternal($model)) {
                return $this->primaryKey[$model] = $primaryKey;
            } else {
                throw new NotImplementedException(['Primary key of `%s` model can not be inferred', $model]);
            }
        } else {
            return $primaryKey;
        }
    }

    public function fields(string $model): array
    {
        if (($fields = $this->fields[$model] ?? null) === null) {
            $that = $this->getThat($model);
            $fieldTypes = $that->fieldTypes();
            if (isset($fieldTypes['_id']) && $fieldTypes['_id'] === 'objectid') {
                unset($fieldTypes['_id']);
            }
            return $this->fields[$model] = array_keys($fieldTypes);
        } else {
            return $fields;
        }
    }

    public function intFields(string $model): array
    {
        if (($intFields = $this->intFields[$model] ?? null) === null) {
            $that = $this->getThat($model);
            $fields = [];
            foreach ($that->fieldTypes() as $field => $type) {
                if ($type === 'int') {
                    $fields[] = $field;
                }
            }

            return $this->intFields[$model] = $fields;
        } else {
            return $intFields;
        }
    }

    public function fieldTypes(string $model): array
    {
        if (($types = $this->fieldTypes[$model] ?? null) === null) {
            list($connection, $collection) = $this->sharding->getAnyShard($model);

            $mongodb = $this->connector->get($connection);
            if (!$docs = $mongodb->fetchAll($collection, [], ['limit' => 1])) {
                throw new RuntimeException(['`:collection` collection has none record', 'collection' => $collection]);
            }

            $types = [];
            foreach ($docs[0] as $field => $value) {
                $type = gettype($value);
                if ($type === 'integer') {
                    $types[$field] = 'int';
                } elseif ($type === 'string') {
                    $types[$field] = 'string';
                } elseif ($type === 'double') {
                    $types[$field] = 'float';
                } elseif ($type === 'boolean') {
                    $types[$field] = 'bool';
                } elseif ($type === 'array') {
                    $types[$field] = 'array';
                } elseif ($value instanceof ObjectId) {
                    if ($field === '_id') {
                        continue;
                    }
                    $types[$field] = 'objectid';
                } else {
                    throw new RuntimeException(['`:field` field value type can not be infer.', 'field' => $field]);
                }
            }

            return $this->fieldTypes[$model] = $types;
        } else {
            return $types;
        }
    }
}