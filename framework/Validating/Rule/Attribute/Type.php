<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Rule\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractRule;
use ManaPHP\Validating\Validation;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Type extends AbstractRule
{
    public function __construct(public string $type, public ?string $message = null)
    {

    }

    public function validate(Validation $validation): bool
    {
        $type = $this->type;
        if ($type === 'int') {
            return $validation->validate(new Integer());
        } elseif ($type === 'float') {
            return $validation->validate(new Double());
        } elseif ($type === 'bool') {
            return $validation->validate(new Boolean());
        } elseif ($type === 'string') {
            return $validation->validate(new StringType());
        } else {
            return true;
        }
    }
}