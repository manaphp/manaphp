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
     * @param array  $ar
     * @param string $key
     *
     * @return array
     */
    public static function indexby($ar, $key)
    {
        if (!is_array($ar)) {
            return $ar;
        }

        $r = [];
        foreach ($ar as $v) {
            $r[is_object($v) ? $v->$key : $v[$key]] = $v;
        }

        return $r;
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
     * @param array        $ar
     * @param string|array $sort
     *
     * @return array
     */
    public static function sort(&$ar, $sort)
    {
        if (is_string($sort)) {
            $ref = array_column($ar, $sort);
            array_multisort($ref, SORT_ASC, $ar);
        } else {
            $params = [];
            foreach ((array)$sort as $k => $v) {
                if (is_int($k)) {
                    $params[] = array_column($ar, $v);
                } else {
                    $params[] = array_column($ar, $k);
                    $params[] = $v;
                }
            }
            $params[] = &$ar;
            /** @noinspection ArgumentUnpackingCanBeUsedInspection */
            /** @noinspection SpellCheckingInspection */
            call_user_func_array('array_multisort', $params);
        }

        return $ar;
    }
}
