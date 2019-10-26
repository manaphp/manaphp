<?php
namespace ManaPHP\Helper;

class Arr
{
    /**
     * @param array $ar
     * @param array $keys
     *
     * @return array
     */
    public static function only($ar, $keys)
    {
        return array_intersect_key($ar, array_fill_keys($keys, null));
    }

    /**
     * @param array $ar
     * @param array $keys
     *
     * @return array
     */
    public static function except($ar, $keys)
    {
        return array_diff_key($ar, array_fill_keys($keys, null));
    }

    public static function dot($ar, $prepend = '')
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

    /**
     * @param array  $ar
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public static function get($ar, $key, $default = null)
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

    /**
     * @param array $ar
     * @param bool  $removeEmpty
     *
     * @return array
     */
    public static function trim($ar, $removeEmpty = true)
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

    /**
     * @param array  $input
     * @param string $field_key
     * @param int    $sort
     *
     * @return array
     */
    public static function unique_column($input, $field_key, $sort = SORT_REGULAR)
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

    /**
     * @param array                 $ar
     * @param string|array|callable $index
     *
     * @return array
     */
    public static function indexby($ar, $index)
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

    /**
     * @param array  $ar
     * @param string $key
     *
     * @return array
     */
    public static function groupby($ar, $key)
    {
        $r = [];

        foreach ($ar as $value) {
            $kv = is_object($value) ? $value->$key : $value[$value];
            $r[$kv][] = $value;
        }

        return $r;
    }

    /**
     * @param array $ar
     * @param array $sort
     *
     * @return array
     */
    public static function sort(&$ar, $sort)
    {
        usort($ar, static function ($a, $b) use ($sort) {
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
        });

        return $ar;
    }

}
