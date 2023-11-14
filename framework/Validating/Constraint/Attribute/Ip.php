<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Constraint\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractConstraint;
use ManaPHP\Validating\Validation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Ip extends AbstractConstraint
{
    public function validate(Validation $validation): bool
    {
        return filter_var($validation->value, FILTER_VALIDATE_IP) !== false;
    }
}