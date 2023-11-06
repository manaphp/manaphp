<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Rule\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractRule;
use ManaPHP\Validating\Validation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Length extends AbstractRule
{
    public function __construct(public int $min, public ?int $max = null, public ?string $message = null)
    {
    }

    public function validate(Validation $validation): bool
    {
        $length = \mb_strlen($validation->value);

        if ($this->max === null) {
            return $length === $this->min;
        } else {
            return $length >= $this->min && $length <= $this->max;
        }
    }
}