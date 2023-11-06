<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Rule\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractRule;
use ManaPHP\Validating\Validation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Defaults extends AbstractRule
{
    public function __construct(public mixed $default, public ?string $message = null)
    {

    }

    public function validate(Validation $validation): bool
    {
        if ($validation->value === null || $validation->value === '') {
            $validation->value = $this->default;
        }

        return true;
    }
}