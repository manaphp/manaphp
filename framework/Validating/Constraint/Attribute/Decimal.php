<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Constraint\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractConstraint;
use ManaPHP\Validating\Validation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Decimal extends AbstractConstraint
{
    public function __construct(public int $M = 10, public int $D = 0, public ?string $message = null)
    {
        parent::__construct($message);
    }

    public function validate(Validation $validation): bool
    {
        $value = (string)$validation->value;

        if (\str_contains($value, '.')) {
            list(, $d) = \explode('.', $value, 2);
            if (\strlen($d) > $this->D) {
                return false;
            }
        }

        return true;
    }
}