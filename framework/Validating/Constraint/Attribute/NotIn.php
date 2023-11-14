<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Constraint\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractConstraint;
use ManaPHP\Validating\Validation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class NotIn extends AbstractConstraint
{
    public function __construct(public array $values, public ?string $message = null)
    {

    }

    public function validate(Validation $validation): bool
    {
        return !\in_array($validation->value, $this->values, true);
    }
}