<?php
namespace ManaPHP\Hierarchy\Adapter;

use ManaPHP\Component;
use ManaPHP\Hierarchy\Exception as HierarchyException;
use ManaPHP\HierarchyInterface;

/**
 * Class ManaPHP\Hierarchy
 *
 * @package ManaPHP
 */
class Path extends Component implements HierarchyInterface
{
    /**
     * @return int
     */
    public static function getBase()
    {
        return 36;
    }

    /**
     * @return int[]
     */
    public static function getLengths()
    {
        return [1, 1, 1];
    }

    /**
     * @return string
     */
    public static function getModelName()
    {
        return null;
    }

    /**
     * @return string
     */
    public static function getCodeName()
    {
        return null;
    }

    /**
     * @param string $node
     *
     * @return bool
     */
    public static function isRoot($node)
    {
        return $node === '';
    }

    /**
     * @param string $node
     *
     * @return int
     */
    public static function getLevel($node)
    {
        if ($node === '') {
            return 0;
        }

        $current_length = 0;
        $node_length = strlen($node);
        foreach (static::getLengths() as $i => $length) {
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
    public static function getMaxLevel()
    {
        return count(static::getLengths());
    }

    /**
     * @return int
     */
    public static function getMaxLength()
    {
        return array_sum(static::getLengths());
    }

    /**
     * @param int $level
     *
     * @return int
     */
    public static function getCapacity($level)
    {
        if ($level === 0) {
            return 1;
        } else {
            /** @noinspection PowerOperatorCanBeUsedInspection */
            return pow(static::getBase(), static::getLengths()[$level - 1]);
        }
    }

    /**
     * @return int[]
     */
    public static function getCapacities()
    {
        $capacities = [];
        foreach (static::getLengths() as $length) {
            /** @noinspection PowerOperatorCanBeUsedInspection */
            $capacities[] = pow(static::getBase(), $length);
        }
        return $capacities;
    }

    /**
     * @param int $level
     *
     * @return int
     */
    public static function getLength($level)
    {
        if ($level === 0) {
            return 0;
        } else {
            return static::getLengths()[$level - 1];
        }
    }

    /**
     * @param string $node
     *
     * @return int
     */
    public static function getParentLength($node)
    {
        if ($node === '') {
            return -1;
        }

        $current_length = 0;
        $node_length = strlen($node);
        foreach (static::getLengths() as $i => $length) {
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
     * @throws \ManaPHP\Hierarchy\Exception
     */
    public static function getParent($node)
    {
        $parent_length = static::getParentLength($node);
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
    public static function getParents($node)
    {
        $parents = [''];

        $node_length = strlen($node);
        $current_length = 0;

        foreach (static::getLengths() as $length) {
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
     * @throws \ManaPHP\Hierarchy\Exception
     */
    public static function getChildren($node)
    {
        $length = static::getChildLength($node);
        if ($length < -1) {
            throw new HierarchyException('xxxx');
        }

        /**
         * @var \ManaPHP\ModelInterface $modelName
         */
        $modelName = static::getModelName();
        $fieldName = static::getCodeName();
        return $modelName::criteria()->whereLike($fieldName, str_pad($node, $length))->distinctField($fieldName);
    }

    /**
     * @param string $node
     *
     * @return array|false
     * @throws \ManaPHP\Hierarchy\Exception
     */
    public static function getSiblings($node)
    {
        $parent = static::getParent($node);
        if ($parent === false) {
            return false;
        }
        /**
         * @var \ManaPHP\ModelInterface $modelName
         */
        $modelName = static::getModelName();
        $codeName = static::getCodeName();
        return $modelName::criteria()->whereLike($codeName, str_pad($parent, strlen($node)))->distinctField($codeName);
    }

    /**
     * @param string $node
     *
     * @return int|-1
     * @throws \ManaPHP\Hierarchy\Exception
     */
    public static function getChildLength($node)
    {
        $lengths = static::getLengths();
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
     * @throws \ManaPHP\Hierarchy\Exception
     */
    public static function hasChild($node)
    {
        /**
         * @var \ManaPHP\ModelInterface $modelName
         */
        $modelName = static::getModelName();

        return $modelName::criteria()->whereStartsWith(static::getCodeName(), $node, static::getChildLength($node))->exists();
    }

    /**
     * @param string $code
     *
     * @return string|false
     */
    public static function calcNextSibling($code)
    {
        $parent_length = static::getParentLength($code);
        if ($parent_length < 0) {
            return false;
        }

        $base = static::getBase();

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
     * @throws \ManaPHP\Hierarchy\Exception
     */
    public static function getMaxSibling($node)
    {
        /**
         * @var \ManaPHP\ModelInterface $modelName
         */
        $modelName = static::getModelName();
        $codeName = static::getCodeName();
        return $modelName::criteria()->whereStartsWith($codeName, static::getParent($node), strlen($node))->max($codeName);
    }

    /**
     * @param string $node
     *
     * @return false|string
     * @throws \ManaPHP\Hierarchy\Exception
     */
    public static function getNextSibling($node)
    {
        $max_sibling = static::getMaxSibling($node);

        return static::calcNextSibling($max_sibling);
    }
}