<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Constraint\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractConstraint;
use ManaPHP\Validating\Validation;
use function mb_strlen;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Length extends AbstractConstraint
{
    public function __construct(public int $min, public ?int $max = null, public ?string $message = null)
    {
        parent::__construct($message);
    }

    public function validate(Validation $validation): bool
    {
        $length = mb_strlen($validation->value);

        if ($this->max === null) {
            return $length === $this->min;
        } else {
            return $length >= $this->min && $length <= $this->max;
        }
    }
}