<?php

namespace ManaPHP\Validating\Rule\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractRule;
use ManaPHP\Validating\Validation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class MinLength extends AbstractRule
{
    public function __construct(public int $min, public ?string $message = null)
    {
    }

    public function validate(Validation $validation): bool
    {
        return mb_strlen($validation->value) >= $this->min;
    }
}