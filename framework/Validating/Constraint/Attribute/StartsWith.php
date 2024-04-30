<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Constraint\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractConstraint;
use ManaPHP\Validating\Validation;
use function is_string;
use function str_starts_with;

#[Attribute(Attribute::TARGET_PROPERTY)]
class StartsWith extends AbstractConstraint
{
    public function __construct(public array|string $needles, public ?string $message = null)
    {
        parent::__construct($message);
    }

    public function validate(Validation $validation): bool
    {
        if (is_string($this->needles)) {
            return str_starts_with($validation->value, $this->needles);
        } else {
            foreach ($this->needles as $needle) {
                if (str_starts_with($validation->value, $needle)) {
                    return true;
                }
            }

            return false;
        }
    }
}