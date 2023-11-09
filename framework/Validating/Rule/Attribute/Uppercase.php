<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Rule\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractRule;
use ManaPHP\Validating\Validation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Uppercase extends AbstractRule
{
    public function validate(Validation $validation): bool
    {
        $validation->value = \strtoupper($validation->value);

        return true;
    }
}