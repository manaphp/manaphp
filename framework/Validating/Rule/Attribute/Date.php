<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Rule\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractRule;
use ManaPHP\Validating\Validation;
use ReflectionProperty;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Date extends AbstractRule
{
    public function validate(Validation $validation): bool
    {
        $value = $validation->value;
        $ts = is_numeric($value) ? (int)$value : strtotime($value);
        if ($ts === false) {
            return false;
        } else {
            if (\is_object($validation->source)) {
                $rProperty = new ReflectionProperty($validation->source, $validation->field);
                if ($rProperty->getType()?->getName() === 'int') {
                    if (\is_string($value)) {
                        $validation->value = $ts;
                    }
                } else {
                    if (\is_int($value)) {
                        $validation->value = \date('Y-m-d H:i:s');
                    }
                }
            }
        }

        return true;
    }
}