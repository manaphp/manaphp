<?php
declare(strict_types=1);

namespace ManaPHP\Model\Attribute;

use Attribute;
use ManaPHP\Helper\Str;
use function is_int;

#[Attribute(Attribute::TARGET_CLASS)]
class ColumnMap
{
    public const  STRATEGY_CAMEL_CASE = 'camelCase';
    public const  STRATEGY_SNAKE_CASE = 'snake_case';

    protected ?string $strategy;
    protected array $map = [];

    public function __construct(?string $strategy = null, array $map = [])
    {
        $this->map = $map;
        $this->strategy = $strategy;
    }

    public function get(array $fields): array
    {
        $map = [];
        if ($this->strategy === null) {
            null;
        } elseif ($this->strategy === self::STRATEGY_SNAKE_CASE) {
            foreach ($fields as $field) {
                $column = Str::snakelize($field);
                if ($column !== $field) {
                    $map[$field] = $column;
                }
            }
        } elseif ($this->strategy === self::STRATEGY_CAMEL_CASE) {
            foreach ($fields as $field) {
                $column = Str::camelize($field);
                if ($column !== $field) {
                    $map[$field] = $column;
                }
            }
        }

        foreach ($this->map as $k => $v) {
            if (is_int($k)) {
                unset($map[$v]);
            } else {
                $map[$k] = $v;
            }
        }

        return $map;
    }
}