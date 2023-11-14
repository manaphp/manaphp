<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Constraint\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractConstraint;
use ManaPHP\Validating\Validation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Lowercase extends AbstractConstraint
{
    public function validate(Validation $validation): bool
    {
        $validation->value = \strtolower($validation->value);

        return true;
    }
}