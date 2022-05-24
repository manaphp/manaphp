<?php
declare(strict_types=1);

namespace ManaPHP\Data\Model\Hierarchy\Adapter;

use ManaPHP\Data\Model\Hierarchy\Exception as HierarchyException;
use ManaPHP\Data\QueryInterface;
use ManaPHP\Helper\Str;

/**
 * @method static QueryInterface query()
 */
trait Path
{
    public static function getHierarchyField(): string
    {
        return Str::snakelize(basename(strtr(static::class, '\\', '/'))) . '_code';
    }

    public static function getHierarchyLengths(): array
    {
        return [1, 1, 1];
    }

    public static function getHierarchyBase(): int
    {
        return 36;
    }

    public static function getHierarchyLevel(string $code): int
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

    public static function getHierarchyMaxLevel(): int
    {
        return count(static::getHierarchyLengths());
    }

    public static function getHierarchyMaxLength(): int
    {
        return array_sum(static::getHierarchyLengths());
    }

    public static function getHierarchyCapacity(int $level): int
    {
        if ($level === 0) {
            return 1;
        } else {
            /** @noinspection PowerOperatorCanBeUsedInspection */
            return pow(static::getHierarchyBase(), static::getHierarchyLengths()[$level - 1]);
        }
    }

    public static function getHierarchyCapacities(): array
    {
        $base = static::getHierarchyBase();

        $capacities = [];
        foreach (static::getHierarchyLengths() as $length) {
            /** @noinspection PowerOperatorCanBeUsedInspection */
            $capacities[] = pow($base, $length);
        }
        return $capacities;
    }

    public static function getHierarchyLength(int $level): int
    {
        if ($level === 0) {
            return 0;
        } else {
            return static::getHierarchyLengths()[$level - 1];
        }
    }

    public static function getHierarchyParentLength(string $code): int
    {
        if ($code === '') {
            return -1;
        }

        $current_length = 0;
        $code_length = strlen($code);
        foreach (static::getHierarchyLengths() as $length) {
            if ($current_length + $length === $code_length) {
                return $current_length;
            } else {
                $current_length += $length;
            }
        }

        return -1;
    }

    public static function getHierarchyParent(string $code): ?string
    {
        $parent_length = static::getHierarchyParentLength($code);
        if ($parent_length < 0) {
            return null;
        }

        return substr($code, 0, $parent_length);
    }

    public static function getHierarchyParents(string $code): ?array
    {
        $parents = [''];

        $node_length = strlen($code);
        $current_length = 0;

        foreach (static::getHierarchyLengths() as $length) {
            if ($current_length + $length === $node_length) {
                return $parents;
            } elseif ($current_length + $length > $node_length) {
                return null;
            } else {
                $current_length += $length;
                $parents[] = substr($code, 0, $current_length);
            }
        }

        return null;
    }

    public static function getHierarchyChildren(string $code): array
    {
        $length = static::getHierarchyChildLength($code);
        if ($length < -1) {
            throw new HierarchyException('xxxx');
        }

        $hierarchyField = static::getHierarchyField();
        return static::query()->whereStartsWith($hierarchyField, $code, $length)->values($hierarchyField);
    }

    public static function getHierarchySiblings(string $code): array
    {
        if ($code === '') {
            return [];
        }

        $parent = static::getHierarchyParent($code);
        $hierarchyField = static::getHierarchyField();
        return static::query()->whereStartsWith($hierarchyField, $parent, strlen($code))->values($hierarchyField);
    }

    public static function getHierarchyChildLength(string $code): int
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

    public static function hierarchyExists(string $code): bool
    {
        return static::query()->where([static::getHierarchyField() => $code])->exists();
    }

    protected static function calcHierarchyNextChild(string $code): ?string
    {
        $parent_length = static::getHierarchyParentLength($code);
        if ($parent_length < 0) {
            return null;
        }

        $base = static::getHierarchyBase();

        $self_length = strlen($code) - $parent_length;
        $sub_node = substr($code, $parent_length, $self_length);
        $next_node_int = (int)base_convert($sub_node, $base, 10) + 1;
        /** @noinspection PowerOperatorCanBeUsedInspection */
        if ($next_node_int >= pow($base, $self_length)) {
            return null;
        } else {
            $parent_code = substr($code, 0, $parent_length);
            $child_code = str_pad(base_convert((string)$next_node_int, 10, $base), $self_length, '0', STR_PAD_LEFT);
            return $parent_code . $child_code;
        }
    }

    public static function getHierarchyNextChild(string $code): ?string
    {
        $hierarchyField = static::getHierarchyField();
        $child_length = static::getHierarchyChildLength($code);
        if ($child_length < 0) {
            return null;
        }

        $max = static::query()->whereStartsWith($hierarchyField, $code, $child_length)->max($hierarchyField);
        if ($max === null) {
            return $code . str_pad('', $child_length - strlen($code) - 1, '0') . '1';
        } else {
            return static::calcHierarchyNextChild($max);
        }
    }
}