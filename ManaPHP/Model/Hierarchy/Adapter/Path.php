<?php
namespace ManaPHP\Model\Hierarchy\Adapter;

use ManaPHP\Model\Hierarchy\Exception as HierarchyException;
use ManaPHP\Utility\Text;

/**
 * Class ManaPHP\Hierarchy
 *
 * @package ManaPHP
 * @method static \ManaPHP\Model\CriteriaInterface criteria()
 */
trait Path
{
    /**
     * @return int
     */
    public static function getHierarchyBase()
    {
        return 36;
    }

    /**
     * @return int[]
     */
    public static function getHierarchyLengths()
    {
        return [1, 1, 1];
    }

    /**
     * @return string
     */
    public static function getHierarchyField()
    {
        return Text::underscore(basename(strtr(get_called_class(), '\\', '/'))) . '_code';
    }

    /**
     * @param string $node
     *
     * @return bool
     */
    public static function isHierarchyRoot($node)
    {
        return $node === '';
    }

    /**
     * @param string $node
     *
     * @return int
     */
    public static function getHierarchyLevel($node)
    {
        if ($node === '') {
            return 0;
        }

        $current_length = 0;
        $node_length = strlen($node);
        foreach (static::getHierarchyLengths() as $i => $length) {
            $current_length += $length;
            if ($current_length === $node_length) {
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
        $capacities = [];
        foreach (static::getHierarchyLengths() as $length) {
            /** @noinspection PowerOperatorCanBeUsedInspection */
            $capacities[] = pow(static::getHierarchyBase(), $length);
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
     * @param string $node
     *
     * @return int
     */
    public static function getHierarchyParentLength($node)
    {
        if ($node === '') {
            return -1;
        }

        $current_length = 0;
        $node_length = strlen($node);
        foreach (static::getHierarchyLengths() as $i => $length) {
            if ($current_length + $length === $node_length) {
                return $current_length;
            } else {
                $current_length += $length;
            }
        }

        return -1;
    }

    /**
     * @param string $node
     *
     * @return bool|string
     * @throws \ManaPHP\Model\Hierarchy\Exception
     */
    public static function getHierarchyParent($node)
    {
        $parent_length = static::getHierarchyParentLength($node);
        if ($parent_length < 0) {
            return false;
        }

        return substr($node, 0, $parent_length);
    }

    /**
     * @param string $node
     *
     * @return array|false
     */
    public static function getHierarchyParents($node)
    {
        $parents = [''];

        $node_length = strlen($node);
        $current_length = 0;

        foreach (static::getHierarchyLengths() as $length) {
            if ($current_length + $length === $node_length) {
                return $parents;
            } elseif ($current_length + $length > $node_length) {
                return false;
            } else {
                $parents[] = substr($node, 0, $current_length + $length);
                $current_length += $length;
            }
        }

        return false;
    }

    /**
     * @param string $node
     *
     * @return string[]
     * @throws \ManaPHP\Model\Hierarchy\Exception
     */
    public static function getHierarchyChildren($node)
    {
        $length = static::getHierarchyChildLength($node);
        if ($length < -1) {
            throw new HierarchyException('xxxx');
        }

        $hierarchyField = static::getHierarchyField();
        return static::criteria()->whereLike($hierarchyField, str_pad($node, $length))->distinctField($hierarchyField);
    }

    /**
     * @param string $node
     *
     * @return array|false
     * @throws \ManaPHP\Model\Hierarchy\Exception
     */
    public static function getHierarchySiblings($node)
    {
        $parent = static::getHierarchyParent($node);
        if ($parent === false) {
            return false;
        }
        $hierarchyField = static::getHierarchyField();
        return static::criteria()->whereLike($hierarchyField, str_pad($parent, strlen($node)))->distinctField($hierarchyField);
    }

    /**
     * @param string $node
     *
     * @return int|-1
     * @throws \ManaPHP\Model\Hierarchy\Exception
     */
    public static function getHierarchyChildLength($node)
    {
        $lengths = static::getHierarchyLengths();
        if ($node === '') {
            return $lengths[0];
        }

        $current_length = 0;
        $node_length = strlen($node);
        foreach ($lengths as $i => $length) {
            $current_length += $length;
            if ($current_length === $node_length) {
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
     * @param string $node
     *
     * @return bool
     * @throws \ManaPHP\Model\Hierarchy\Exception
     */
    public static function hierarchyHasChild($node)
    {
        return static::criteria()->whereStartsWith(static::getHierarchyField(), $node, static::getHierarchyChildLength($node))->exists();
    }

    /**
     * @param string $code
     *
     * @return string|false
     */
    public static function calcHierarchyNextSibling($code)
    {
        $parent_length = static::getHierarchyParentLength($code);
        if ($parent_length < 0) {
            return false;
        }

        $base = static::getHierarchyBase();

        $self_length = strlen($code) - $parent_length;
        $sub_node = substr($code, $parent_length, $self_length);
        $next_node_int = base_convert($sub_node, $base, 10) + 1;
        /** @noinspection PowerOperatorCanBeUsedInspection */
        if ($next_node_int >= pow($base, $self_length)) {
            return false;
        } else {
            return substr($code, 0, $parent_length) . str_pad(base_convert($next_node_int, 10, $base), $self_length, '0', STR_PAD_LEFT);
        }
    }

    /**
     * @param string $node
     *
     * @return string
     * @throws \ManaPHP\Model\Hierarchy\Exception
     */
    public static function getHierarchyMaxSibling($node)
    {
        $hierarchyField = static::getHierarchyField();
        return static::criteria()->whereStartsWith($hierarchyField, static::getHierarchyParent($node), strlen($node))->max($hierarchyField);
    }

    /**
     * @param string $node
     *
     * @return false|string
     * @throws \ManaPHP\Model\Hierarchy\Exception
     */
    public static function getHierarchyNextSibling($node)
    {
        $max_sibling = static::getHierarchyMaxSibling($node);

        return static::calcHierarchyNextSibling($max_sibling);
    }

    public static function getHierarchyMaxChild($code)
    {
        $hierarchyField = static::getHierarchyField();
        $max = static::criteria()->whereStartsWith($hierarchyField, $code, static::getHierarchyChildLength($code))->max($hierarchyField);
        if ($max === null) {
            $max = $code . str_pad('0', static::getHierarchyChildLength($code) - strlen($code), '0');
        }

        return $max;
    }

    /**
     * @param string $code
     *
     * @return string|false
     */
    public static function calcHierarchyNextChild($code)
    {
        $parent_length = static::getHierarchyParentLength($code);
        if ($parent_length < 0) {
            return false;
        }

        $base = static::getHierarchyBase();

        $self_length = strlen($code) - $parent_length;
        $sub_node = substr($code, $parent_length, $self_length);
        $next_node_int = base_convert($sub_node, $base, 10) + 1;
        /** @noinspection PowerOperatorCanBeUsedInspection */
        if ($next_node_int >= pow($base, $self_length)) {
            return false;
        } else {
            return substr($code, 0, $parent_length) . str_pad(base_convert($next_node_int, 10, $base), $self_length, '0', STR_PAD_LEFT);
        }
    }

    public static function getHierarchyNextChild($node)
    {
        $max_child = static::getHierarchyMaxChild($node);
        return static::calcHierarchyNextChild($max_child);
    }
}