<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Constraint\Attribute;

use Attribute;
use ManaPHP\Exception;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Helper\Str;
use ManaPHP\Model\ModelInterface;
use ManaPHP\Validating\AbstractConstraint;
use ManaPHP\Validating\Validation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Exists extends AbstractConstraint
{
    public function validate(Validation $validation): bool
    {
        if (!$validation->source instanceof ModelInterface) {
            throw new MisuseException(\sprintf('%s is not a model', \get_class($validation->source)));
        }

        $value = $validation->value;
        $field = $validation->field;
        if (!$value) {
            return true;
        }

        if (preg_match('#^(.*)_id$#', $validation->field, $match)) {
            $modelName = $validation->source::class;
            $className = substr($modelName, 0, strrpos($modelName, '\\') + 1) . Str::pascalize($match[1]);
            if (!class_exists($className)) {
                $className = 'App\\Models\\' . Str::pascalize($match[1]);
            }
        } else {
            throw new InvalidValueException(['validate `{1}` failed: related model class is not provided', $field]);
        }

        if (!class_exists($className)) {
            throw new InvalidValueException(['validate `{1}` failed: `{2}` class is not exists.', $field, $className]);
        }

        try {
            $className::get($value);
        } catch (Exception $exception) {
            return false;
        }

        return true;
    }
}