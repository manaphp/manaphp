<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Rule\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractRule;
use ManaPHP\Validating\Validation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Account extends AbstractRule
{
    public function validate(Validation $validation): bool
    {
        return preg_match('#^[a-z][a-z\d_]{2,}$#', $validation->value) === 1;
    }
}