<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Constraint\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractConstraint;
use ManaPHP\Validating\Validation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Uuid extends AbstractConstraint
{
    public function validate(Validation $validation): bool
    {
        return preg_match('#^[\da-f]{8}(-[\da-f]{4}){3}-[\da-f]{12}$#i', $validation->value) === 1;
    }
}