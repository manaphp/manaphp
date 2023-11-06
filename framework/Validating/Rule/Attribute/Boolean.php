<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Rule\Attribute;

use Attribute;
use ManaPHP\Validating\AbstractRule;
use ManaPHP\Validating\Validation;
use ReflectionProperty;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Boolean extends AbstractRule
{
    public function __construct(
        public array $true = [1, '1', 'true', 'on', 'yes'],
        public array $false = [0, '0', 'false', 'off', 'no'],
        public ?string $message = null,
    ) {

    }

    public function validate(Validation $validation): bool
    {
        if (!\is_bool($validation->value)) {
            if (\in_array($validation->value, $this->true, true)) {
                $validation->value = true;
            } elseif (\in_array($validation->value, $this->false, true)) {
                $validation->value = false;
            } else {
                return false;
            }
        }

        if (\is_bool($validation->value) && \is_object($validation->source)) {
            $rProperty = new ReflectionProperty($validation->source, $validation->field);
            if ($rProperty->getType()?->getName() === 'int') {
                $validation->value = (int)$validation->value;
            }
        }

        return true;
    }
}