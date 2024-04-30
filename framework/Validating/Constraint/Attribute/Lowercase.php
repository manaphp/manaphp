<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Constraint\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractConstraint;
use ManaPHP\Validating\Validation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Lowercase extends AbstractConstraint
{
    public function __construct(protected bool $sanitize = true, protected ?string $message = null)
    {
        parent::__construct($message);
    }

    public function validate(Validation $validation): bool
    {
        if ($this->sanitize) {
            $validation->value = \strtolower($validation->value);
            return true;
        } else {
            return $validation->value === \strtolower($validation->value);
        }
    }
}