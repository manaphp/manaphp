<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Validating\Rule\Attribute\Required;
use ManaPHP\Validating\Rule\Attribute\Type;
use ManaPHP\Validating\ValidatorInterface;

class Input implements InputInterface
{
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ValidatorInterface $validator;

    public function has(string $name): bool
    {
        return $this->request->input($name) !== null;
    }

    public function get(string $name, mixed $default = null): mixed
    {
        if (($value = $this->request->input($name, $default)) === null) {
            return $this->validator->validateValue($name, null, [new Required()]);
        } else {
            return $value;
        }
    }

    public function type(string $type, string $name, array $rules = [], mixed $default = null): mixed
    {
        if (($value = $this->request->input($name, $default)) === null) {
            return $this->validator->validateValue($name, null, [new Required()]);
        } else {
            \array_unshift($rules, new Type($type));

            return $this->validator->validateValue($name, $value, $rules);
        }
    }

    public function string(string $name, array $rules = [], ?string $default = null): string
    {
        return $this->type('string', $name, $rules, $default);
    }

    public function float(string $name, array $rules = [], ?float $default = null): float
    {
        return $this->type('float', $name, $rules, $default);
    }

    public function int(string $name, array $rules = [], ?int $default = null): int
    {
        return $this->type('int', $name, $rules, $default);
    }

    public function array(string $name, array $rules = [], ?array $default = null): array
    {
        return $this->type('array', $name, $rules, $default);
    }

    public function bool(string $name, array $rules = [], ?bool $default = null): bool
    {
        return $this->type('bool', $name, $rules, $default);
    }

    public function bit(string $name, array $rules = [], ?int $default = null): int
    {
        return $this->type('bit', $name, $rules, $default);
    }

    public function mixed(string $name, array $rules = [], mixed $default = null): mixed
    {
        return $this->type('mixed', $name, $rules, $default);
    }
}