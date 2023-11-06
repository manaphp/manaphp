<?php
declare(strict_types=1);

namespace ManaPHP\Validating;

interface RuleInterface
{
    public function validate(Validation $validation): bool;

    public function getMessage(): string;
}