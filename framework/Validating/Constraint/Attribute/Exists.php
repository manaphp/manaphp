<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Constraint\Attribute;

use Attribute;
use ManaPHP\Exception;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Helper\Str;
use ManaPHP\Persistence\Entity;
use ManaPHP\Validating\AbstractConstraint;
use ManaPHP\Validating\Validation;
use function get_class;
use function sprintf;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Exists extends AbstractConstraint
{
    public function validate(Validation $validation): bool
    {
        if (!$validation->source instanceof Entity) {
            throw new MisuseException(sprintf('%s is not a entity', get_class($validation->source)));
        }

        $value = $validation->value;
        $field = $validation->field;
        if (!$value) {
            return true;
        }

        if (preg_match('#^(.*)_id$#', $validation->field, $match)) {
            $entityName = $validation->source::class;
            $className = substr($entityName, 0, strrpos($entityName, '\\') + 1) . Str::pascalize($match[1]);
            if (!class_exists($className)) {
                $className = 'App\\Entities\\' . Str::pascalize($match[1]);
            }
        } else {
            throw new InvalidValueException(['validate `{1}` failed: related entity class is not provided', $field]);
        }

        if (!class_exists($className)) {
            throw new InvalidValueException(['validate `{1}` failed: `{2}` class is not exists.', $field, $className]);
        }

        try {
            $className::get($value);
        } catch (Exception) {
            return false;
        }

        return true;
    }
}