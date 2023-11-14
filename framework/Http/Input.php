<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Validating\Constraint\Attribute\Required;
use ManaPHP\Validating\Constraint\Attribute\Type;
use ManaPHP\Validating\ValidatorInterface;

class Input implements InputInterface
{
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected ValidatorInterface $validator;

    public function all(): array
    {
        return $this->request->all();
    }

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

    public function type(string $type, string $name, array $constraints = [], mixed $default = null): mixed
    {
        if (($value = $this->request->input($name, $default)) === null) {
            return $this->validator->validateValue($name, null, [new Required()]);
        } else {
            \array_unshift($constraints, new Type($type));

            return $this->validator->validateValue($name, $value, $constraints);
        }
    }

    public function string(string $name, array $constraints = [], ?string $default = null): string
    {
        return $this->type('string', $name, $constraints, $default);
    }

    public function float(string $name, array $constraints = [], ?float $default = null): float
    {
        return $this->type('float', $name, $constraints, $default);
    }

    public function int(string $name, array $constraints = [], ?int $default = null): int
    {
        return $this->type('int', $name, $constraints, $default);
    }

    public function array(string $name, array $constraints = [], ?array $default = null): array
    {
        return $this->type('array', $name, $constraints, $default);
    }

    public function bool(string $name, array $constraints = [], ?bool $default = null): bool
    {
        return $this->type('bool', $name, $constraints, $default);
    }

    public function bit(string $name, array $constraints = [], ?int $default = null): int
    {
        return $this->type('bit', $name, $constraints, $default);
    }

    public function mixed(string $name, array $constraints = [], mixed $default = null): mixed
    {
        return $this->type('mixed', $name, $constraints, $default);
    }
}