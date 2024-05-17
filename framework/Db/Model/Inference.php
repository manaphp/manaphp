<?php
declare(strict_types=1);

namespace ManaPHP\Db\Model;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Persistence\EntityMetadataInterface;
use ManaPHP\Persistence\ThoseInterface;
use function count;
use function in_array;

class Inference implements InferenceInterface
{
    #[Autowired] protected ThoseInterface $those;
    #[Autowired] protected MetadataInterface $metadata;
    #[Autowired] protected EntityMetadataInterface $entityMetadata;

    protected array $primaryKey = [];
    protected array $fields = [];
    protected array $intFields = [];

    protected function primaryKeyInternal(string $entityClass): ?string
    {
        $fields = $this->entityMetadata->getFields($entityClass);

        if (in_array('id', $fields, true)) {
            return 'id';
        }

        $prefix = lcfirst(($pos = strrpos($entityClass, '\\')) === false ? $entityClass : substr($entityClass, $pos + 1));
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
                $primaryKeys = $this->metadata->getPrimaryKeyAttributes($entityClass);
                if (count($primaryKeys) !== 1) {
                    throw new NotSupportedException('only support one primary key');
                }
                $primaryKey = $primaryKeys[0];
                $columnMap = $this->entityMetadata->getColumnMap($entityClass);
                return $this->primaryKey[$entityClass] = array_search($primaryKey, $columnMap, true) ?: $primaryKey;
            }
        } else {
            return $primaryKey;
        }
    }
}