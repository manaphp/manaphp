<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Rule\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractRule;
use ManaPHP\Validating\Validation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Alnum extends AbstractRule
{
    public function validate(Validation $validation): bool
    {
        return preg_match('#^[a-zA-Z\d]+$#', $validation->value) === 1;
    }
}