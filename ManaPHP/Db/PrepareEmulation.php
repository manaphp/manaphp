<?php

namespace ManaPHP\Db {

    /**
     * ManaPHP\Db\PrepareEmulation
     */
    class PrepareEmulation
    {
        /**
         * @var \PDO
         */
        protected $_pdo;

        public function __construct($pdo)
        {
            $this->_pdo = $pdo;
        }

        /**
         * @param mixed $value
         * @param int   $type
         * @param int   $preservedStrLength
         *
         * @return int|string
         */
        protected function _parseValue($value, $type, $preservedStrLength)
        {
            if ($type === \PDO::PARAM_STR) {
                if ($preservedStrLength > 0 && strlen($value) >= $preservedStrLength) {
                    return $this->_pdo->quote(substr($value, 0, $preservedStrLength) . '...');
                } else {
                    return $this->_pdo->quote($value);
                }
            } elseif ($type === \PDO::PARAM_INT) {
                return $value;
            } elseif ($type === \PDO::PARAM_NULL) {
                return 'NULL';
            } elseif ($type === \PDO::PARAM_BOOL) {
                return (int)$value;
            } else {
                return $value;
            }
        }

        /**
         * @param mixed $data
         *
         * @return int
         */
        protected function _inferType($data)
        {
            if (is_string($data)) {
                return \PDO::PARAM_STR;
            } elseif (is_int($data)) {
                return \PDO::PARAM_INT;
            } elseif (is_bool($data)) {
                return \PDO::PARAM_BOOL;
            } elseif ($data === null) {
                return \PDO::PARAM_NULL;
            } else {
                return \PDO::PARAM_STR;
            }
        }

        /**
         * @param string $sqlStatement
         * @param array  $bindParams
         * @param array  $bindTypes
         * @param int    $preservedStrLength
         *
         * @return mixed
         */
        public function emulate($sqlStatement, $bindParams = null, $bindTypes = null, $preservedStrLength = -1)
        {
            if ($bindParams === null || count($bindParams) === 0) {
                return $sqlStatement;
            }

            if (isset($bindParams[0])) {
                $pos = 0;

                $preparedStatement = preg_replace_callback('/(\?)/',
                    function () use ($bindParams, $bindTypes, &$pos, $preservedStrLength) {
                        $type = isset($bindTypes[$pos]) ? $bindTypes[$pos] : $this->_inferType($bindParams[$pos]);

                        return $this->_parseValue($bindParams[$pos++], $type, $preservedStrLength);
                    }, $sqlStatement);

                if ($pos !== count($bindParams)) {
                    return 'infer failed:' . $sqlStatement;
                } else {
                    return $preparedStatement;
                }
            } else {
                $replaces = [];
                foreach ($bindParams as $key => $value) {
                    $type = isset($bindTypes[$key]) ? $bindTypes[$key] : $this->_inferType($bindParams[$key]);
                    $replaces[$key] = $this->_parseValue($value, $type, $preservedStrLength);
                }

                return strtr($sqlStatement, $replaces);
            }
        }
    }
}
