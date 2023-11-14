<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Constraint\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractConstraint;
use ManaPHP\Validating\Validation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Email extends AbstractConstraint
{
    public function validate(Validation $validation): bool
    {
        if (filter_var($validation->value, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        $validation->value = \strtolower($validation->value);

        return true;
    }
}