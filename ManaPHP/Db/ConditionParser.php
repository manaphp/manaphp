<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/27
 * Time: 15:53
 */
namespace ManaPHP\Db {

    use ManaPHP\Db\ConditionParser\Exception as ParserException;

    class ConditionParser
    {
        /**
         * @param array|string $conditions
         * @param array|null   $binds
         *
         * @return string
         * @throws \ManaPHP\Db\ConditionParser\Exception
         */
        public function parse($conditions, &$binds)
        {
            $binds = [];

            if ($conditions === null) {
                return '';
            }

            if (is_string($conditions)) {
                return $conditions;
            }

            if (!is_array($conditions)) {
                throw new ParserException('invalid condition: ' . json_encode($conditions));
            }

            if (count($conditions) === 0) {
                return '';
            }

            $list = [];
            foreach ($conditions as $k => $v) {
                if (is_int($k)) {
                    $list[] = $v;
                    continue;
                }

                if (is_scalar($v) || $v === null) {
                    $data = $v;
                    $column = $k;
                } elseif (is_array($v)) {
                    if (count($v) === 1) {
                        $data = $v[0];
                        $column = $k;
                    } elseif (count($v) === 2) {
                        list($data, $column) = $v;
                    } else {
                        throw new ParserException('too many items:' . json_encode($v));
                    }
                } else {
                    throw new ParserException('bind value must be scalar: ' . json_encode($v));
                }

                $list[] = "`$k`=:$column";
                $binds[$column] = $data;
            }

            return implode(' AND ', $list);
        }
    }
}