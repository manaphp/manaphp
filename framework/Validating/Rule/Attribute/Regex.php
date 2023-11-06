<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Rule\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractRule;
use ManaPHP\Validating\Validation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Regex extends AbstractRule
{
    public function __construct(public string $pattern, public ?string $message = null)
    {

    }

    public function validate(Validation $validation): bool
    {
        return \preg_match($this->pattern, $validation->value) === 1;
    }
}