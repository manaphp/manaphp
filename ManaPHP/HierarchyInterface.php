<?php
namespace ManaPHP;

interface HierarchyInterface
{
    /**
     * @param string $node
     *
     * @return bool
     */
    public static function isRoot($node);

    /**
     * @param string $node
     *
     * @return int
     */
    public static function getLevel($node);

    /**
     * @return int
     */
    public static function getMaxLevel();

    /**
     * @return int
     */
    public static function getMaxLength();

    /**
     * @param int $level
     *
     * @return int
     */
    public static function getCapacity($level);

    /**
     * @return int[]
     */
    public static function getCapacities();

    /**
     * @param string $node
     *
     * @return int
     */
    public static function getParentLength($node);

    /**
     * @param string $node
     *
     * @return string|false
     */
    public static function getParent($node);

    /**
     * @param string $node
     *
     * @return array|false
     */
    public static function getParents($node);

    /**
     * @param string $node
     *
     * @return string[]
     */
    public static function getChildren($node);

    /**
     * @param string $node
     *
     * @return int|-1
     */
    public static function getChildLength($node);

    /**
     * @param string $node
     *
     * @return array|false
     */
    public static function getSiblings($node);

    /**
     * @param string $node
     *
     * @return bool
     */
    public static function hasChild($node);

    /**
     * @param string $node
     *
     * @return string
     */
    public static function getMaxSibling($node);

    /**
     * @param string $node
     *
     * @return string|false
     */
    public static function getNextSibling($node);
}