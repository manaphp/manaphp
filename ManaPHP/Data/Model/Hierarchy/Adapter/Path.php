<?php

namespace ManaPHP\Data\Model\Hierarchy\Adapter;

use ManaPHP\Data\Model\Hierarchy\Exception as HierarchyException;
use ManaPHP\Data\QueryInterface;
use ManaPHP\Helper\Str;

/**
 * @method static QueryInterface query()
 */
trait Path
{
    /**
     * @return string
     */
    public static function getHierarchyField()
    {
        return Str::underscore(basename(strtr(static::class, '\\', '/'))) . '_code';
    }

    /**
     * @return int[]
     */
    public static function getHierarchyLengths()
    {
        return [1, 1, 1];
    }

    /**
     * @return int
     */
    public static function getHierarchyBase()
    {
        return 36;
    }

    /**
     * @param string $code
     *
     * @return int
     */
    public static function getHierarchyLevel($code)
    {
        if ($code === '') {
            return 0;
        }

        $current_length = 0;
        $code_length = strlen($code);
        foreach (static::getHierarchyLengths() as $i => $length) {
            $current_length += $length;
            if ($current_length === $code_length) {
                return $i + 1;
            }
        }

        return -1;
    }

    /**
     * @return int
     */
    public static function getHierarchyMaxLevel()
    {
        return count(static::getHierarchyLengths());
    }

    /**
     * @return int
     */
    public static function getHierarchyMaxLength()
    {
        return array_sum(static::getHierarchyLengths());
    }

    /**
     * @param int $level
     *
     * @return int
     */
    public static function getHierarchyCapacity($level)
    {
        if ($level === 0) {
            return 1;
        } else {
            /** @noinspection PowerOperatorCanBeUsedInspection */
            return pow(static::getHierarchyBase(), static::getHierarchyLengths()[$level - 1]);
        }
    }

    /**
     * @return int[]
     */
    public static function getHierarchyCapacities()
    {
        $base = static::getHierarchyBase();

        $capacities = [];
        foreach (static::getHierarchyLengths() as $length) {
            /** @noinspection PowerOperatorCanBeUsedInspection */
            $capacities[] = pow($base, $length);
        }
        return $capacities;
    }

    /**
     * @param int $level
     *
     * @return int
     */
    public static function getHierarchyLength($level)
    {
        if ($level === 0) {
            return 0;
        } else {
            return static::getHierarchyLengths()[$level - 1];
        }
    }

    /**
     * @param string $code
     *
     * @return int
     */
    public static function getHierarchyParentLength($code)
    {
        if ($code === '') {
            return -1;
        }

        $current_length = 0;
        $code_length = strlen($code);
        foreach (static::getHierarchyLengths() as $i => $length) {
            if ($current_length + $length === $code_length) {
                return $current_length;
            } else {
                $current_length += $length;
            }
        }

        return -1;
    }

    /**
     * @param string $code
     *
     * @return string|false
     */
    public static function getHierarchyParent($code)
    {
        $parent_length = static::getHierarchyParentLength($code);
        if ($parent_length < 0) {
            return false;
        }

        return substr($code, 0, $parent_length);
    }

    /**
     * @param string $code
     *
     * @return array|false
     */
    public static function getHierarchyParents($code)
    {
        $parents = [''];

        $node_length = strlen($code);
        $current_length = 0;

        foreach (static::getHierarchyLengths() as $length) {
            if ($current_length + $length === $node_length) {
                return $parents;
            } elseif ($current_length + $length > $node_length) {
                return false;
            } else {
                $current_length += $length;
                $parents[] = substr($code, 0, $current_length);
            }
        }

        return false;
    }

    /**
     * @param string $code
     *
     * @return string[]
     * @throws \ManaPHP\Data\Model\Hierarchy\Exception
     */
    public static function getHierarchyChildren($code)
    {
        $length = static::getHierarchyChildLength($code);
        if ($length < -1) {
            throw new HierarchyException('xxxx');
        }

        $hierarchyField = static::getHierarchyField();
        return static::query()->whereStartsWith($hierarchyField, $code, $length)->values($hierarchyField);
    }

    /**
     * @param string $code
     *
     * @return array|false
     */
    public static function getHierarchySiblings($code)
    {
        if ($code === '') {
            return [];
        }

        $parent = static::getHierarchyParent($code);
        $hierarchyField = static::getHierarchyField();
        return static::query()->whereStartsWith($hierarchyField, $parent, strlen($code))->values($hierarchyField);
    }

    /**
     * @param string $code
     *
     * @return int|-1
     */
    public static function getHierarchyChildLength($code)
    {
        $lengths = static::getHierarchyLengths();
        if ($code === '') {
            return $lengths[0];
        }

        $current_length = 0;
        $code_length = strlen($code);
        foreach ($lengths as $i => $length) {
            $current_length += $length;
            if ($current_length === $code_length) {
                if ($i >= count($lengths) - 1) {
                    return -1;
                } else {
                    return $current_length + $lengths[$i + 1];
                }
            }
        }

        return -1;
    }

    /**
     * @param string $code
     *
     * @return bool
     */
    public static function hierarchyExists($code)
    {
        return static::query()->whereEq(static::getHierarchyField(), $code)->exists();
    }

    /**
     * @param string $code
     *
     * @return string|false
     */
    protected static function _calcHierarchyNextChild($code)
    {
        $parent_length = static::getHierarchyParentLength($code);
        if ($parent_length < 0) {
            return false;
        }

        $base = static::getHierarchyBase();

        $self_length = strlen($code) - $parent_length;
        $sub_node = substr($code, $parent_length, $self_length);
        $next_node_int = (int)base_convert($sub_node, $base, 10) + 1;
        /** @noinspection PowerOperatorCanBeUsedInspection */
        if ($next_node_int >= pow($base, $self_length)) {
            return false;
        } else {
            $parent_code = substr($code, 0, $parent_length);
            $child_code = str_pad(base_convert($next_node_int, 10, $base), $self_length, '0', STR_PAD_LEFT);
            return $parent_code . $child_code;
        }
    }

    /**
     * @param string $code
     *
     * @return string|false
     */
    public static function getHierarchyNextChild($code)
    {
        $hierarchyField = static::getHierarchyField();
        $child_length = static::getHierarchyChildLength($code);
        if ($child_length < 0) {
            return false;
        }

        $max = static::query()->whereStartsWith($hierarchyField, $code, $child_length)->max($hierarchyField);
        if ($max === null) {
            return $code . str_pad('', $child_length - strlen($code) - 1, '0') . '1';
        } else {
            return static::_calcHierarchyNextChild($max);
        }
    }
}