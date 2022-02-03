<?php
declare(strict_types=1);

namespace ManaPHP\Data\Db\SqlFragment;

use ManaPHP\Data\Db\SqlFragmentable;

class Increment implements SqlFragmentable
{
    protected mixed $value;
    protected ?string $operator = null;
    protected array $bind;
    protected string $field;

    public function __construct(mixed $value, string $operator = '+', array $bind = [])
    {
        $this->value = $value;
        $this->operator = $operator;
        $this->bind = $bind;
    }

    public function setField(string $name): static
    {
        $this->field = $name;

        return $this;
    }

    public function getSql(): string
    {
        if ($this->operator !== null) {
            return "$this->field = $this->field $this->operator :$this->field";
        } else {
            return $this->value;
        }
    }

    public function getBind(): array
    {
        if ($this->operator !== null) {
            return [$this->field => $this->value];
        } else {
            return $this->bind;
        }
    }
}