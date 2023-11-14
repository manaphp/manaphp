<?php

namespace ManaPHP\Validating\Constraint\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractConstraint;
use ManaPHP\Validating\Validation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Max extends AbstractConstraint
{
    public function __construct(public float $max, public ?string $message = null)
    {
    }

    public function validate(Validation $validation): bool
    {
        return $validation->value <= $this->max;
    }
}