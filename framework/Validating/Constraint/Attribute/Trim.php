<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Constraint\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractConstraint;
use ManaPHP\Validating\Validation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Trim extends AbstractConstraint
{
    public function __construct(public string $characters = " \n\r\t\v\x00", public ?string $message = null)
    {
        parent::__construct($message);
    }

    public function validate(Validation $validation): bool
    {
        $validation->value = \trim($validation->value, $this->characters);

        return true;
    }
}