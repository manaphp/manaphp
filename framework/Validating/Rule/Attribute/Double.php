<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Rule\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractRule;
use ManaPHP\Validating\Validation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Double extends AbstractRule
{
    public function validate(Validation $validation): bool
    {
        if (!\is_int($validation->value) && !\is_float($validation->value)) {
            if (filter_var($validation->value, FILTER_VALIDATE_FLOAT) !== false
                && preg_match('#^[+\-]?[\d.]+$#', $validation->value) === 1
            ) {
                $validation->value = (float)$validation->value;
            } else {
                return false;
            }
        }

        return true;
    }
}