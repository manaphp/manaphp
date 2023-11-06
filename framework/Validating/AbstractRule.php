<?php
declare(strict_types=1);

namespace ManaPHP\Validating;

abstract class AbstractRule implements RuleInterface
{
    public ?string $message = null;

    public function getMessage(): string
    {
        return $this->message ?? static::class;
    }
}