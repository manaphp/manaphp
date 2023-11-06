<?php

namespace ManaPHP\Validating\Rule\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractRule;
use ManaPHP\Validating\Validation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Max extends AbstractRule
{
    public function __construct(public float $max, public ?string $message = null)
    {
    }

    public function validate(Validation $validation): bool
    {
        return $validation->value <= $this->max;
    }
}