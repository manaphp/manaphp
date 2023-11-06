<?php

namespace ManaPHP\Validating\Rule\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractRule;
use ManaPHP\Validating\Validation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Min extends AbstractRule
{
    public function __construct(public float $min, public ?string $message = null)
    {
    }

    public function validate(Validation $validation): bool
    {
        return $validation->value >= $this->min;
    }
}