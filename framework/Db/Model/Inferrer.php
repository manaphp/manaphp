<?php
declare(strict_types=1);

namespace ManaPHP\Db\Model;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Model\ModelsInterface;
use ManaPHP\Model\ThoseInterface;

class Inferrer implements InferrerInterface
{
    #[Autowired] protected ThoseInterface $those;
    #[Autowired] protected MetadataInterface $metadata;
    #[Autowired] protected ModelsInterface $models;

    protected array $primaryKey = [];
    protected array $fields = [];
    protected array $intFields = [];

    protected function primaryKeyInternal(string $model): ?string
    {
        $fields = $this->models->getFields($model);

        if (in_array('id', $fields, true)) {
            return 'id';
        }

        $prefix = lcfirst(($pos = strrpos($model, '\\')) === false ? $model : substr($model, $pos + 1));
        if (in_array($tryField = $prefix . '_id', $fields, true)) {
            return $tryField;
        } elseif (in_array($tryField = $prefix . 'Id', $fields, true)) {
            return $tryField;
        }

        $table = $this->models->getTable($model);
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
                $primaryKeys = $this->metadata->getPrimaryKeyAttributes($model);
                if (count($primaryKeys) !== 1) {
                    throw new NotSupportedException('only support one primary key');
                }
                $primaryKey = $primaryKeys[0];
                $columnMap = $this->models->getColumnMap($model);
                return $this->primaryKey[$model] = array_search($primaryKey, $columnMap, true) ?: $primaryKey;
            }
        } else {
            return $primaryKey;
        }
    }

    public function fields(string $model): array
    {
        if (($fields = $this->fields[$model] ?? null) === null) {
            $fields = [];
            foreach (get_class_vars($model) as $field => $value) {
                if ($value === null && $field[0] !== '_') {
                    $fields[] = $field;
                }
            }
            return $this->fields[$model] = $fields ?: $this->metadata->getAttributes($model);
        } else {
            return $fields;
        }
    }

    public function intFields(string $model): array
    {
        if (($fields = $this->intFields[$model] ?? null) === null) {
            if (($columnMap = $this->models->getColumnMap($model)) !== []) {
                foreach ($this->metadata->getIntTypeAttributes($model) as $field) {
                    $fields[] = array_search($field, $columnMap, true) ?: $field;
                }
            } else {
                $fields = $this->metadata->getIntTypeAttributes($model);
            }

            return $this->intFields[$model] = $fields;
        } else {
            return $fields;
        }
    }

}