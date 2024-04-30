<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Constraint\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractConstraint;
use ManaPHP\Validating\Validation;
use function preg_match;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Regex extends AbstractConstraint
{
    public function __construct(public string $pattern, public ?string $message = null)
    {
        parent::__construct($message);
    }

    public function validate(Validation $validation): bool
    {
        return preg_match($this->pattern, $validation->value) === 1;
    }
}