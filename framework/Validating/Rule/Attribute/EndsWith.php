<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Rule\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractRule;
use ManaPHP\Validating\Validation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class EndsWith extends AbstractRule
{
    public function __construct(public array|string $needles, public ?string $message = null)
    {

    }

    public function validate(Validation $validation): bool
    {
        if (\is_string($this->needles)) {
            return \str_ends_with($validation->value, $this->needles);
        } else {
            foreach ($this->needles as $needle) {
                if (\str_ends_with($validation->value, $needle)) {
                    return true;
                }
            }

            return false;
        }
    }
}