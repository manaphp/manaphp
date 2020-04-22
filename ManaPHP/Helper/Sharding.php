<?php

namespace ManaPHP\Helper;

use ManaPHP\Exception\MisuseException;
use ManaPHP\Helper\Sharding\ShardingTooManyException;

class Sharding
{
    /**
     * @param string $str
     *
     * @return array
     */
    public static function explode($str)
    {
        if (strpos($str, ',') !== false) {
            return preg_split('#[\s,]+#', $str, -1, PREG_SPLIT_NO_EMPTY);
        } elseif (strpos($str, '%') !== false) {
            if (preg_match('#([\w.]+):(\w+)%(\d+)#', $str, $match)) {
                list(, $base, , $divisor) = $match;
                if ($divisor === '1') {
                    return [$base];
                } else {
                    $r = [];
                    for ($i = 0; $i < $divisor; $i++) {
                        $r[] = "{$base}_$i";
                    }
                }
                return $r;
            } else {
                throw new MisuseException($str);
            }
        } else {
            return [$str];
        }
    }

    /**
     * @param string $strategy
     * @param array  $context
     *
     * @return array
     */
    public static function modulo($strategy, $context)
    {
        if (preg_match('#^([\w.]+):(\w+)%(\d+)$#', $strategy, $match) !== 1) {
            throw new MisuseException($strategy);
        }

        list(, $base, $key, $divisor) = $match;
        $values = isset($context[$key]) ? (array)$context[$key] : range(0, $divisor - 1);

        $flags = [];
        $r = [];
        foreach ($values as $value) {
            $remainder = $value % $divisor;

            if (!isset($flags[$remainder])) {
                $flags[$remainder] = true;
                $r[] = "{$base}_{$remainder}";
            }
        }
        return $r;
    }

    /**
     * @param string $db
     * @param string $table
     *
     * @return array
     */
    public static function all($db, $table)
    {
        $dbs = self::explode($db);
        $tables = self::explode($table);

        return array_fill_keys($dbs, $tables);
    }

    /**
     * @param string $db
     * @param string $table
     * @param mixed  $context
     *
     * @return array
     */
    public static function multiple($db, $table, $context)
    {
        if ($context === null || $context === []) {
            return self::all($db, $table);
        }

        $db_is_modulo = strpos($db, '%') !== false;
        $table_is_modulo = strpos($table, '%') !== false;

        if (!$db_is_modulo && !$table_is_modulo) {
            return self::all($db, $table);
        } elseif (!$db_is_modulo && $table_is_modulo) {
            $dbs = $db === '' ? [''] : preg_split('#[\s,]+#', $db, -1, PREG_SPLIT_NO_EMPTY);
            $tables = self::modulo($table, $context);
        } elseif ($db_is_modulo && !$table_is_modulo) {
            $dbs = self::modulo($db, $context);
            $tables = preg_split('#[\s,]+#', $table, -1, PREG_SPLIT_NO_EMPTY);
        } else {
            if (preg_match('#^([\w.]+):(\w+)%(\d+)$#', $db, $match) !== 1) {
                throw new MisuseException($db);
            }
            list(, $db_base, $db_key, $db_divisor) = $match;

            if (preg_match('#^([\w.]+):(\w+)%(\d+)$#', $table, $match) !== 1) {
                throw new MisuseException($table);
            }
            list(, $table_base, $table_key, $table_divisor) = $match;

            if ($db_key === $table_key) {
                $shards = [];
                $key = $db_key;
                $divisor = $db_divisor * $table_divisor;
                $flags = [];
                $values = isset($context[$key]) ? (array)$context[$key] : range(0, $divisor - 1);
                foreach ($values as $value) {
                    $dividend = $value % $divisor;

                    $quotient = (int)($dividend / $db_divisor);
                    $remainder = $dividend % $table_divisor;

                    if (!isset($flags[$quotient][$remainder])) {
                        $flags[$quotient][$remainder] = true;
                        $shards["{$db_base}_{$quotient}"][] = "{$table_base}_{$remainder}";
                    }
                }
                return $shards;
            } else {
                $db_has_context = isset($context[$db_key]);
                $table_has_context = isset($context[$table_key]);

                if (!$db_has_context && !$table_has_context) {
                    return self::all($db, $table);
                } elseif (!$db_has_context && $table_has_context) {
                    $dbs = self::explode($db);
                    $tables = self::modulo($table, $context);
                } elseif ($db_has_context && !$table_has_context) {
                    $dbs = self::modulo($db, $context);
                    $tables = self::explode($table);
                } elseif (!is_scalar($context[$db_key]) && !is_scalar($context[$table_key])) {
                    $dbs = self::modulo($db, $context);
                    $tables = self::explode($table);
                } else {
                    $dbs = self::modulo($db, $context);
                    $tables = self::modulo($table, $context);
                }
            }
        }
        return $tables ? array_fill_keys($dbs, $tables) : [];
    }

    /**
     * @param string $db
     * @param string $source
     * @param mixed  $context
     *
     * @return array
     */
    public static function unique($db, $source, $context)
    {
        $shards = self::multiple($db, $source, $context);
        if (count($shards) !== 1) {
            throw new ShardingTooManyException(['too many dbs: `dbs`', 'dbs' => array_keys($shards)]);
        }

        $tables = current($shards);
        if (count($tables) !== 1) {
            throw new ShardingTooManyException(['too many tables: `tables`', 'tables' => $tables]);
        }

        return [key($shards), $tables[0]];
    }
}