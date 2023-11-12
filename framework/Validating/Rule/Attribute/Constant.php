<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Rule\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractRule;
use ManaPHP\Validating\Validation;
use ReflectionClass;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Constant extends AbstractRule
{
    public function __construct(public ?string $name = null, public ?string $message = null)
    {

    }

    public function validate(Validation $validation): bool
    {
        $rClass = new ReflectionClass($validation->source);

        $prefix = \strtoupper($this->name ?? $validation->field) . '_';

        foreach ($rClass->getConstants() as $name => $value) {
            if (!\str_starts_with($name, $prefix)) {
                continue;
            }

            if ($value === $validation->value) {
                return true;
            }
        }

        return false;
    }
}