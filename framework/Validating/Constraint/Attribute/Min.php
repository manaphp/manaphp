<?php

namespace ManaPHP\Validating\Constraint\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractConstraint;
use ManaPHP\Validating\Validation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Min extends AbstractConstraint
{
    public function __construct(public float $min, public ?string $message = null)
    {
    }

    public function validate(Validation $validation): bool
    {
        return $validation->value >= $this->min;
    }
}