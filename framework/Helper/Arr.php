<?php
declare(strict_types=1);

namespace ManaPHP\Helper;

use ManaPHP\Exception\MisuseException;
use function in_array;
use function is_array;
use function is_int;
use function is_object;
use function is_scalar;
use function is_string;

class Arr
{
    public static function only(array $ar, array $keys): array
    {
        return array_intersect_key($ar, array_fill_keys($keys, null));
    }

    public static function except(array $ar, array $keys): array
    {
        return array_diff_key($ar, array_fill_keys($keys, null));
    }

    public static function dot(array $ar, string $prepend = ''): array
    {
        $r = [];

        foreach ($ar as $key => $value) {
            if (is_array($value) && $value) {
                /** @noinspection SlowArrayOperationsInLoopInspection */
                $r = array_merge($r, self::dot($value, $prepend . $key . '.'));
            } else {
                $r[$prepend . $key] = $value;
            }
        }
        return $r;
    }

    public static function get(array $ar, string $key, mixed $default = null): mixed
    {
        if (!$key) {
            return $ar;
        }

        if (($pos = strrpos($key, '.')) === false) {
            return $ar[$key] ?? null;
        }

        $t = $ar;
        foreach (explode('.', substr($key, 0, $pos)) as $segment) {
            if (!isset($t[$segment]) || !is_array($t[$segment])) {
                return $default;
            }
            $t = $t[$segment];
        }

        $last = substr($key, $pos + 1);
        return $t[$last] ?? $default;
    }

    public static function trim(array $ar, bool $removeEmpty = true): array
    {
        foreach ($ar as $k => $v) {
            if (is_string($v)) {
                if ($v === '') {
                    if ($removeEmpty) {
                        unset($ar[$k]);
                    }
                } else {
                    $v = trim($v);
                    if ($v === '' && $removeEmpty) {
                        unset($ar[$k]);
                    } else {
                        $ar[$k] = $v;
                    }
                }
            } elseif (is_array($v)) {
                $ar[$k] = self::trim($v, $removeEmpty);
            }
        }

        return $ar;
    }

    public static function unique_column(array $input, string $field_key, int $sort = SORT_REGULAR): array
    {
        $values = [];
        foreach ($input as $item) {
            $value = is_array($item) ? $item[$field_key] : $item->$field_key;
            if (!in_array($value, $values, true)) {
                $values[] = $value;
            }
        }

        if ($sort !== null) {
            sort($values, $sort);
        }

        return $values;
    }

    public static function indexby(array $ar, mixed $index): array
    {
        $rows = [];
        if (is_scalar($index)) {
            foreach ($ar as $row) {
                $rows[$row[$index]] = $row;
            }
        } elseif (is_array($index)) {
            $k = key($index);
            $v = current($index);
            foreach ($ar as $row) {
                $rows[$row[$k]] = $row[$v];
            }
        } else {
            foreach ($ar as $row) {
                $rows[$index($row)] = $row;
            }
        }

        return $rows;
    }

    public static function groupby(array $ar, string $key): array
    {
        $r = [];

        foreach ($ar as $value) {
            $kv = is_object($value) ? $value->$key : $value[$value];
            $r[$kv][] = $value;
        }

        return $r;
    }

    public static function sort(array &$ar, array $sort): array
    {
        usort(
            $ar, static function ($a, $b) use ($sort) {
            foreach ($sort as $k => $v) {
                $field = is_int($k) ? $v : $k;

                $first = $a[$field];
                $second = $b[$field];

                $r = is_string($first) ? strcmp($first, $second) : $first - $second;
                if ($r > 0) {
                    return (is_int($k) || $v === SORT_ASC || $v === 'ASC' || $v === 'asc') ? 1 : -1;
                } elseif ($r < 0) {
                    return (is_int($k) || $v === SORT_ASC || $v === 'ASC' || $v === 'asc') ? -1 : 1;
                }
            }
            return 0;
        }
        );

        return $ar;
    }

    public static function aggregate(array $rows, array $aggs, array $group = []): array
    {
        if (!$rows) {
            return [];
        }

        $grouped_rows = [];
        foreach ($rows as $item) {
            $key = '';
            foreach ($group as $g) {
                if ($key === '') {
                    $key = $item[$g];
                } else {
                    $key .= ':' . $item[$g];
                }
            }
            $grouped_rows[$key][] = $item;
        }

        $result = [];
        foreach ($grouped_rows as $v) {
            $row = [];

            foreach ($group as $gk => $gv) {
                $field = is_int($gk) ? $gv : $gk;
                $row[$field] = $v[0][$field];
            }

            foreach ($aggs as $field => $agg) {
                if ($agg === 'MAX') {
                    $values = array_column($v, $field);
                    $max = max($values);
                    $row[$field] = $max === false ? null : $max;
                } elseif ($agg === 'MIN') {
                    $values = array_column($v, $field);
                    $max = min($values);
                    $row[$field] = $max === false ? null : $max;
                } elseif ($agg === 'COUNT') {
                    $values = array_column($v, $field);
                    $row[$field] = array_sum($values);
                } elseif ($agg === 'SUM') {
                    $values = array_column($v, $field);
                    $row[$field] = array_sum($values);
                } elseif ($agg === 'AVG') {
                    $sum_field = $field . '_sum';
                    if (!isset($v[0][$sum_field])) {
                        throw new MisuseException(['`{1}` not in `{2}`', $sum_field, implode(',', array_keys($v[0]))]);
                    }
                    $sum = array_sum(array_column($v, $sum_field));

                    $count_field = $field . '_count';
                    if (!isset($v[0][$count_field])) {
                        throw new MisuseException(['`{1}` not in `{2}`', $count_field, implode(',', array_keys($v[0]))]
                        );
                    }
                    $count = array_sum(array_column($v, $count_field));

                    $row[$field] = $count ? $sum / $count : null;
                } else {
                    /** @noinspection PhpExpressionResultUnusedInspection */
                    null;
                }
            }

            $result[] = $row;
        }

        return $result;
    }
}
