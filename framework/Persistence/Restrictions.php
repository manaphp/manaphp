<?php
declare(strict_types=1);

namespace ManaPHP\Persistence;

use function is_string;
use function preg_match;
use function strpos;
use function substr;
use function trim;

class Restrictions implements RestrictionsInterface
{
    protected array $restrictions;

    public static function of(array $data, array $filters): static
    {
        $restrictions = new static();

        foreach ($filters as $k => $v) {
            if (is_string($k)) {
                $restrictions->eq($k, $v);
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
                $restrictions->eq($v, $value);
            }
        }

        return $restrictions;
    }

    public function eq(string $field, mixed $value): static
    {
        $this->restrictions[] = ['eq', $field, $value];
        return $this;
    }

    public function get(): array
    {
        return $this->restrictions;
    }
}