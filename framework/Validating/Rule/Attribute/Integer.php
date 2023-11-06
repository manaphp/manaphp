<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Rule\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractRule;
use ManaPHP\Validating\Validation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Integer extends AbstractRule
{
    public function validate(Validation $validation): bool
    {
        if (!\is_int($validation->value)) {
            if (preg_match('#^[+\-]?\d+$#', $validation->value) === 1) {
                $validation->value = (int)$validation->value;
            } else {
                return false;
            }
        }

        return true;
    }
}