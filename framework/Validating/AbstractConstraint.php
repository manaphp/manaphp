<?php
declare(strict_types=1);

namespace ManaPHP\Validating;

abstract class AbstractConstraint implements ConstraintInterface
{
    public function __construct(protected ?string $message = null)
    {

    }

    public function getMessage(): string
    {
        return $this->message ?? static::class;
    }
}