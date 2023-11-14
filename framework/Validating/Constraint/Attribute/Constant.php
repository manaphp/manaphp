<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Constraint\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractConstraint;
use ManaPHP\Validating\Validation;
use ReflectionClass;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Constant extends AbstractConstraint
{
    public function __construct(public ?string $name = null, public ?string $message = null)
    {
        parent::__construct($message);
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