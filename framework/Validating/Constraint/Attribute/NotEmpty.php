<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Constraint\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractConstraint;
use ManaPHP\Validating\Validation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class NotEmpty extends AbstractConstraint
{
    public function validate(Validation $validation): bool
    {
        return isset($validation->value) && $validation->value !== '';
    }
}