<?php
declare(strict_types=1);

namespace ManaPHP\Validating;

abstract class AbstractConstraint implements ConstraintInterface
{
    public ?string $message = null;

    public function getMessage(): string
    {
        return $this->message ?? static::class;
    }
}