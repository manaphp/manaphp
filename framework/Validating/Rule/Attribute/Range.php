<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Rule\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractRule;
use ManaPHP\Validating\Validation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Range extends AbstractRule
{
    public function __construct(public int $min, public int $max, public ?string $message = null)
    {
    }

    public function validate(Validation $validation): bool
    {
        return $validation->value >= $this->min && $validation->value <= $this->max;
    }
}