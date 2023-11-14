<?php

namespace ManaPHP\Validating\Constraint\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractConstraint;
use ManaPHP\Validating\Validation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class MinLength extends AbstractConstraint
{
    public function __construct(public int $min, public ?string $message = null)
    {
    }

    public function validate(Validation $validation): bool
    {
        return mb_strlen($validation->value) >= $this->min;
    }
}