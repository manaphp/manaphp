<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Rule\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractRule;
use ManaPHP\Validating\Validation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Xdigit extends AbstractRule
{
    public function validate(Validation $validation): bool
    {
        return preg_match('#^[\da-fA-F]+$#', $validation->value) === 1;
    }
}