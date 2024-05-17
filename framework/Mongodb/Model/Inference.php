<?php
declare(strict_types=1);

namespace ManaPHP\Mongodb\Model;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception\NotImplementedException;
use ManaPHP\Exception\RuntimeException;
use ManaPHP\Mongodb\EntityManagerInterface;
use ManaPHP\Mongodb\MongodbConnectorInterface;
use ManaPHP\Persistence\EntityMetadataInterface;
use ManaPHP\Persistence\ShardingInterface;
use ManaPHP\Persistence\ThoseInterface;
use MongoDB\BSON\ObjectId;
use function gettype;
use function in_array;

class Inference implements InferenceInterface
{
    #[Autowired] protected ThoseInterface $those;
    #[Autowired] protected MongodbConnectorInterface $connector;
    #[Autowired] protected ShardingInterface $sharding;
    #[Autowired] protected EntityMetadataInterface $entityMetadata;
    #[Autowired] protected EntityManagerInterface $entityManager;

    protected array $primaryKey = [];
    protected array $fields = [];
    protected array $intFields = [];
    protected array $fieldTypes = [];

    protected function primaryKeyInternal(string $entityClass): ?string
    {
        $fields = $this->entityMetadata->getFields($entityClass);

        if (in_array('id', $fields, true)) {
            return 'id';
        }

        $prefix = lcfirst(
            ($pos = strrpos($entityClass, '\\')) === false ? $entityClass : substr($entityClass, $pos + 1)
        );
        if (in_array($tryField = $prefix . '_id', $fields, true)) {
            return $tryField;
        } elseif (in_array($tryField = $prefix . 'Id', $fields, true)) {
            return $tryField;
        }

        $table = $this->entityMetadata->getTable($entityClass);
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

    public function primaryKey(string $entityClass): string
    {
        if (($primaryKey = $this->primaryKey[$entityClass] ?? null) === null) {
            if ($primaryKey = $this->primaryKeyInternal($entityClass)) {
                return $this->primaryKey[$entityClass] = $primaryKey;
            } else {
                throw new NotImplementedException(['Primary key of `{1}` entity can not be inferred', $entityClass]);
            }
        } else {
            return $primaryKey;
        }
    }

    public function fields(string $entityClass): array
    {
        if (($fields = $this->fields[$entityClass] ?? null) === null) {
            $fieldTypes = $this->entityManager->fieldTypes($entityClass);
            if (isset($fieldTypes['_id']) && $fieldTypes['_id'] === 'objectid') {
                unset($fieldTypes['_id']);
            }
            return $this->fields[$entityClass] = array_keys($fieldTypes);
        } else {
            return $fields;
        }
    }

    public function intFields(string $entityClass): array
    {
        if (($intFields = $this->intFields[$entityClass] ?? null) === null) {
            $fields = [];
            foreach ($this->entityManager->fieldTypes($entityClass) as $field => $type) {
                if ($type === 'int') {
                    $fields[] = $field;
                }
            }

            return $this->intFields[$entityClass] = $fields;
        } else {
            return $intFields;
        }
    }

    public function fieldTypes(string $entityClass): array
    {
        if (($types = $this->fieldTypes[$entityClass] ?? null) === null) {
            list($connection, $collection) = $this->sharding->getAnyShard($entityClass);

            $mongodb = $this->connector->get($connection);
            if (!$docs = $mongodb->fetchAll($collection, [], ['limit' => 1])) {
                throw new RuntimeException(['`{collection}` collection has none record', 'collection' => $collection]);
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
                    throw new RuntimeException(['`{field}` field value type can not be infer.', 'field' => $field]);
                }
            }

            return $this->fieldTypes[$entityClass] = $types;
        } else {
            return $types;
        }
    }
}