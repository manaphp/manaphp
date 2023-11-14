<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Constraint\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractConstraint;
use ManaPHP\Validating\Validation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Defaults extends AbstractConstraint
{
    public function __construct(public mixed $default, public ?string $message = null)
    {
        parent::__construct($message);
    }

    public function validate(Validation $validation): bool
    {
        if ($validation->value === null || $validation->value === '') {
            $validation->value = $this->default;
        }

        return true;
    }
}