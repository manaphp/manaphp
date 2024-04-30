<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Constraint\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractConstraint;
use ManaPHP\Validating\Validation;
use function in_array;

#[Attribute(Attribute::TARGET_PROPERTY)]
class In extends AbstractConstraint
{
    public function __construct(public array $values, public ?string $message = null)
    {
        parent::__construct($message);
    }

    public function validate(Validation $validation): bool
    {
        return in_array($validation->value, $this->values, true);
    }
}