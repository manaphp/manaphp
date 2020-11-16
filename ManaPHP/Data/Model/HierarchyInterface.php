<?php

namespace ManaPHP\Data\Model;

interface HierarchyInterface
{
    /**
     * @param string $node
     *
     * @return bool
     */
    public static function isHierarchyRoot($node);

    /**
     * @param string $node
     *
     * @return int
     */
    public static function getHierarchyLevel($node);

    /**
     * @return int
     */
    public static function getHierarchyMaxLevel();

    /**
     * @return int
     */
    public static function getHierarchyMaxLength();

    /**
     * @param int $level
     *
     * @return int
     */
    public static function getHierarchyCapacity($level);

    /**
     * @return int[]
     */
    public static function getHierarchyCapacities();

    /**
     * @param string $node
     *
     * @return int
     */
    public static function getHierarchyParentLength($node);

    /**
     * @param string $node
     *
     * @return string|false
     */
    public static function getHierarchyParent($node);

    /**
     * @param string $node
     *
     * @return array|false
     */
    public static function getHierarchyParents($node);

    /**
     * @param string $node
     *
     * @return string[]
     */
    public static function getHierarchyChildren($node);

    /**
     * @param string $node
     *
     * @return int|-1
     */
    public static function getHierarchyChildLength($node);

    /**
     * @param string $node
     *
     * @return array|false
     */
    public static function getHierarchySiblings($node);

    /**
     * @param string $node
     *
     * @return bool
     */
    public static function hierarchyHasChild($node);

    /**
     * @param string $node
     *
     * @return string
     */
    public static function getHierarchyMaxSibling($node);

    /**
     * @param string $node
     *
     * @return string|false
     */
    public static function getHierarchyNextSibling($node);
}