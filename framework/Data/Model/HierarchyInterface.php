<?php
declare(strict_types=1);

namespace ManaPHP\Data\Model;

interface HierarchyInterface
{
    public static function isHierarchyRoot(string $node): bool;

    public static function getHierarchyLevel(string $node): int;

    public static function getHierarchyMaxLevel(): int;

    public static function getHierarchyMaxLength(): int;

    public static function getHierarchyCapacity(int $level): int;

    public static function getHierarchyCapacities(): array;

    public static function getHierarchyParentLength(string $node): int;

    public static function getHierarchyParent(string $node): false|string;

    public static function getHierarchyParents(string $node): false|array;

    public static function getHierarchyChildren(string $node): array;

    public static function getHierarchyChildLength(string $node): int;

    public static function getHierarchySiblings(string $node): false|array;

    public static function hierarchyHasChild(string $node): bool;

    public static function getHierarchyMaxSibling(string $node): string;

    public static function getHierarchyNextSibling(string $node): false|string;
}