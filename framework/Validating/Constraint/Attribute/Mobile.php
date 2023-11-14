<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Constraint\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractConstraint;
use ManaPHP\Validating\Validation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Mobile extends AbstractConstraint
{
    public function validate(Validation $validation): bool
    {
        return preg_match('#^1[3-8]\d{9}$#', $validation->value) === 1;
    }
}