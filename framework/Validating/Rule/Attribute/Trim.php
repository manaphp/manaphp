<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Rule\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractRule;
use ManaPHP\Validating\Validation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Trim extends AbstractRule
{
    public function validate(Validation $validation): bool
    {
        $validation->value = \trim($validation->value);

        return true;
    }
}