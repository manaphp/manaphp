<?php
namespace ManaPHP\Counter\Adapter {

    use ManaPHP\Counter;

    /**
     * Class Db
     * @package ManaPHP\Counter\Adapter
     *
     * CREATE TABLE `manaphp_counter` (
     *  `name` char(128) CHARACTER SET latin1 NOT NULL,
     *  `counter` bigint(20) NOT NULL,
     *  `created_time` int(11) NOT NULL,
     *  `updated_time` int(11) NOT NULL,
     *   PRIMARY KEY (`name`)
     *   ) ENGINE=InnoDB DEFAULT CHARSET=utf8
     */
    class Db extends Counter
    {
        protected $_table = 'manaphp_counter';

        /**
         * Db constructor.
         *
         * @param string|array $options
         */
        public function __construct($options = [])
        {
            if (is_string($options)) {
                $options['table'] = $options;
            }

            if (isset($options['table'])) {
                $this->_table = $options['table'];
            }
        }

        protected function _getKey($key)
        {
            if (is_string($key)) {
                return $key;
            } else {
                return implode('/', $key);
            }
        }

        public function _get($key)
        {
            $key = $this->_getKey($key);
            /** @noinspection SqlNoDataSourceInspection */
            /** @noinspection SqlDialectInspection */
            $r = $this->db->fetchOne('SELECT counter FROM ' . $this->_table . ' WHERE name=:name', ['name' => $key]);
            if (!$r) {
                return 0;
            } else {
                return $r['counter'];
            }
        }

        /**
         * @param string $key
         * @param int    $step
         *
         * @return int
         * @throws \ManaPHP\Counter\Adapter\Exception|\ManaPHP\Db\Exception
         */
        public function _increment($key, $step)
        {
            $key = $this->_getKey($key);

            $time = time();
            /** @noinspection SqlNoDataSourceInspection */
            /** @noinspection SqlDialectInspection */
            $r = $this->db->fetchOne('SELECT counter FROM ' . $this->_table . ' WHERE name=:name', ['name' => $key]);
            if (!$r) {
                try {
                    $this->db->insert($this->_table, ['name' => $key, 'counter' => $step, 'created_time' => $time, 'updated_time' => $time]);
                    return $step;
                } catch (\Exception $e) {
                    //maybe this record has been inserted by other request.
                }

                /** @noinspection SqlNoDataSourceInspection */
                /** @noinspection SqlDialectInspection */
                $r = $this->db->fetchOne('SELECT counter FROM ' . $this->_table . ' WHERE name=:name', ['name' => $key]);
            }

            $old_counter = $r['counter'];
            for ($i = 0; $i < 100; $i++) {
                /** @noinspection SqlNoDataSourceInspection */
                /** @noinspection SqlDialectInspection */
                $sql = "UPDATE $this->_table SET counter =counter+:step, updated_time =:updated_time WHERE name =:name AND counter =:old_counter";
                $r = $this->db->execute($sql, ['name' => $key, 'step' => $step, 'old_counter' => $old_counter, 'updated_time' => $time]);
                if ($r === 1) {
                    return $old_counter + $step;
                }
            }

            throw new Exception('update counter failed: ' . $key);
        }

        public function _delete($key)
        {
            $key = $this->_getKey($key);

            $this->db->delete($this->_table, ['name' => $key]);
        }
    }
}