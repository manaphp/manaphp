<?php
declare(strict_types=1);

namespace ManaPHP\Data\Model;

use ManaPHP\Component;
use ManaPHP\Data\Db\Model as DbModel;
use ManaPHP\Data\ModelInterface;
use ManaPHP\Data\Mongodb\Model as MongodbModel;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

/**
 * @property-read \ManaPHP\Data\Db\Model\MetadataInterface $modelMetadata
 * @property-read \ManaPHP\Data\Model\ThoseInterface       $those
 * @property-read \ManaPHP\Data\Model\ManagerInterface     $modelManager
 */
class Linter extends Component
{
    public const METHOD_FIELDS_BAD = 'bad';
    public const METHOD_FIELDS_GOOD = 'good';
    protected string $class;
    protected ReflectionClass $reflection;
    protected ModelInterface $model;

    public function __construct(string|ModelInterface $model)
    {
        $this->class = is_string($model) ? $model : $model::class;
        $this->model = is_string($model) ? $this->those->get($model) : $model;
        $this->reflection = new ReflectionClass($model);
    }

    public function lintMethodFields(): array
    {
        $r = [];
        $model = $this->model;

        foreach ($this->reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $rMethod) {
            if ($rMethod->getDeclaringClass()->getName() !== $this->class) {
                continue;
            }

            $methodName = $rMethod->getName();
            if ($methodName === 'fields') {
                $some = $this->modelManager->getFields($model::class);
                if ($model instanceof DbModel) {
                    $all = $this->getPropertyFields();
                    if (!$all) {
                        $all = $this->modelMetadata->getAttributes($model::class);
                    }
                } elseif ($model instanceof MongodbModel) {
                    $all = array_keys($model->fieldTypes());
                } else {
                    $all = $some;
                }
            } elseif ($methodName === 'rules') {
                $some = array_keys($model->rules());
                $all = $this->modelManager->getFields($model::class);
            } else {
                continue;
            }

            $r[$methodName][self::METHOD_FIELDS_BAD] = array_diff($some, $all);
            $r[$methodName][self::METHOD_FIELDS_GOOD] = array_diff($all, $some);
        }

        return $r;
    }

    public function getPropertyFields(): array
    {
        $fields = [];
        foreach ($this->reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $rProperty) {
            if ($rProperty->isStatic()) {
                continue;
            }

            $fields[] = $rProperty->getName();
        }

        return $fields;
    }

    public function lintRealPropertyFields(): array
    {
        $model = $this->model;

        $properties = [];
        foreach ($this->reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $rProperty) {
            if ($rProperty->isStatic()) {
                continue;
            }

            $properties[] = $rProperty->getName();
        }

        return array_diff($properties, $this->modelManager->getFields($model::class));
    }

    public function getMagicFields(): array
    {
        $comment = $this->reflection->getDocComment();

        if (!$comment) {
            return [];
        }

        $fields = [];

        if (preg_match_all('#\*\s+@property\s+(\S+)\s+\$(\w+)#', $comment, $matches, PREG_SET_ORDER)) {
            foreach ((array)$matches as $match) {
                list(, $type, $field) = $match;
                $fields[$field] = $type;
            }
        }

        return $fields;
    }

    public function lintMagicPropertyFields(): array
    {
        $fields = [];
        foreach ($this->getMagicFields() as $field => $type) {
            if (str_contains($type, '\\')) {
                continue;
            }

            $fields[] = $field;
        }

        $model = $this->model;
        return array_diff($fields, $this->modelManager->getFields($model::class));
    }
}
