<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Constraint\Attribute;

use Attribute;
use ManaPHP\Exception\MisuseException;
use ManaPHP\Helper\SuppressWarnings;
use ManaPHP\Validating\AbstractConstraint;
use ManaPHP\Validating\Validation;
use function in_array;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_iterable;
use function is_string;
use function method_exists;
use function sprintf;
use function ucfirst;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Type extends AbstractConstraint
{
    public function __construct(
        public string $type,
        public array $true = [1, '1', 'true', 'on', 'yes'],
        public array $false = [0, '0', 'false', 'off', 'no'],
        public ?string $message = null
    ) {
        parent::__construct($this->message);
    }

    public function validate(Validation $validation): bool
    {
        $method = 'validate' . ucfirst($this->type);
        if (method_exists($this, $method)) {
            return $this->$method($validation);
        } else {
            throw new MisuseException(sprintf('%s type is not supported', $this->type));
        }
    }

    public function validateBool(Validation $validation): bool
    {
        if (!is_bool($validation->value)) {
            if (in_array($validation->value, $this->true, true)) {
                $validation->value = true;
            } elseif (in_array($validation->value, $this->false, true)) {
                $validation->value = false;
            } else {
                return false;
            }
        }

        return true;
    }

    public function validateBit(Validation $validation): bool
    {
        if (!is_bool($validation->value)) {
            if (in_array($validation->value, $this->true, true)) {
                $validation->value = 1;
            } elseif (in_array($validation->value, $this->false, true)) {
                $validation->value = 0;
            } else {
                return false;
            }
        } else {
            $validation->value = (int)$validation->value;
        }

        return true;
    }

    public function validateFloat(Validation $validation): bool
    {
        if (is_int($validation->value) || is_float($validation->value)) {
            return true;
        } elseif (is_string($validation->value)) {
            if (filter_var($validation->value, FILTER_VALIDATE_FLOAT) !== false
                && preg_match('#^[+\-]?[\d.]+$#', $validation->value) === 1
            ) {
                $validation->value = (float)$validation->value;
            } else {
                return false;
            }
        } else {
            return false;
        }

        return true;
    }

    public function validateInt(Validation $validation): bool
    {
        if (is_int($validation->value)) {
            return true;
        } elseif (is_bool($validation->value)) {
            $validation->value = (int)$validation->value;
        } elseif (is_string($validation->value)) {
            if (preg_match('#^[+\-]?\d+$#', $validation->value) === 1) {
                $validation->value = (int)$validation->value;
            } else {
                return false;
            }
        } else {
            return false;
        }

        return true;
    }

    public function validateString(Validation $validation): bool
    {
        return is_string($validation->value);
    }

    public function validateArray(Validation $validation): bool
    {
        return is_array($validation->value);
    }

    public function validateMixed(Validation $validation): bool
    {
        SuppressWarnings::unused($validation);

        return true;
    }

    public function validateObject(Validation $validation): bool
    {
        return is_object($validation->value);
    }

    public function validateIterable(Validation $validation): bool
    {
        return is_iterable($validation->value);
    }
}