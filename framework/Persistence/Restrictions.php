<?php
declare(strict_types=1);

namespace ManaPHP\Persistence;

use ManaPHP\Persistence\Restriction\Any;
use ManaPHP\Persistence\Restriction\Between;
use ManaPHP\Persistence\Restriction\Contains;
use ManaPHP\Persistence\Restriction\EndsWith;
use ManaPHP\Persistence\Restriction\Eq;
use ManaPHP\Persistence\Restriction\Gt;
use ManaPHP\Persistence\Restriction\Gte;
use ManaPHP\Persistence\Restriction\In;
use ManaPHP\Persistence\Restriction\Lt;
use ManaPHP\Persistence\Restriction\Lte;
use ManaPHP\Persistence\Restriction\Mod;
use ManaPHP\Persistence\Restriction\Ne;
use ManaPHP\Persistence\Restriction\Nin;
use ManaPHP\Persistence\Restriction\StartsWith;
use ManaPHP\Persistence\Restriction\TimeBetween;
use ManaPHP\Query\QueryInterface;
use function is_string;
use function preg_match;
use function strpos;
use function substr;
use function trim;

class Restrictions
{
    protected array $restrictions = [];

    public static function create(): static
    {
        return new static();
    }

    public static function of(array $data, array $filters): static
    {
        $instance = new static();

        $any = [];
        foreach ($filters as $k => $v) {
            if (is_string($k)) {
                $any[$k] = $v;
            } else {
                preg_match('#^\w+#', ($pos = strpos($v, '.')) ? substr($v, $pos + 1) : $v, $match);
                $field = $match[0];

                if (!isset($data[$field])) {
                    continue;
                }
                $value = $data[$field];
                if (is_string($value)) {
                    $value = trim($value);
                    if ($value === '') {
                        continue;
                    }
                }
                $any[$v] = $value;
            }
        }

        return $instance->any($any);
    }

    public function eq(string $field, mixed $value): static
    {
        return $this->add(new Eq($field, $value));
    }

    public function ne(string|array $field, mixed $value): static
    {
        return $this->add(new Ne($field, $value));
    }

    public function gt(string $field, mixed $value): static
    {
        return $this->add(new Gt($field, $value));
    }

    public function gte(string|array $field, mixed $value): static
    {
        return $this->add(new Gte($field, $value));
    }

    public function lt(string|array $field, mixed $value): static
    {
        return $this->add(new Lt($field, $value));
    }

    public function lte(string|array $field, mixed $value): static
    {
        return $this->add(new Lte($field, $value));
    }

    public function in(string|array $field, array $values): static
    {
        return $this->add(new In($field, $values));
    }

    public function nin(string $field, array $values): static
    {
        return $this->add(new Nin($field, $values));
    }

    public function mod(string $field, int $divisor, int $remainder): static
    {
        return $this->add(new Mod($field, $divisor, $remainder));
    }

    public function startsWith(string|array $field, string $value, ?int $length = null): static
    {
        return $this->add(new StartsWith($field, $value, $length));
    }

    public function endsWith(string|array $fields, string $value): static
    {
        return $this->add(new EndsWith($fields, $value));
    }

    public function contains(string|array $fields, string $value): static
    {
        return $this->add(new Contains($fields, $value));
    }

    public function timeBetween(string $field, mixed $start, mixed $end): static
    {
        return $this->add(new TimeBetween($field, $start, $end));
    }

    public function between(string $field, mixed $start, mixed $end): static
    {
        return $this->add(new Between($field, $start, $end));
    }

    public function any(array $filters): static
    {
        return $this->add(new Any($filters));
    }

    public function add(RestrictionInterface $restriction): static
    {
        $this->restrictions[] = $restriction;

        return $this;
    }

    public function apply(QueryInterface $query): void
    {
        foreach ($this->restrictions as $restriction) {
            $restriction->apply($query);
        }
    }

    public function get(): array
    {
        return $this->restrictions;
    }
}