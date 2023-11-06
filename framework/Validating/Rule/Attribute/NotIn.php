<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Rule\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractRule;
use ManaPHP\Validating\Validation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class NotIn extends AbstractRule
{
    public function __construct(public array $values, public ?string $message = null)
    {

    }

    public function validate(Validation $validation): bool
    {
        return !\in_array($validation->value, $this->values, true);
    }
}