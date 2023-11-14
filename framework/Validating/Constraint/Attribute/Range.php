<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Constraint\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractConstraint;
use ManaPHP\Validating\Validation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Range extends AbstractConstraint
{
    public function __construct(public int $min, public int $max, public ?string $message = null)
    {
        parent::__construct($message);
    }

    public function validate(Validation $validation): bool
    {
        return $validation->value >= $this->min && $validation->value <= $this->max;
    }
}