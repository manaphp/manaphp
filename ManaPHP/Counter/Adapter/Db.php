<?php
namespace ManaPHP\Counter\Adapter {

    use ManaPHP\Counter;
    use ManaPHP\Counter\Exception;

    class Db extends Counter
    {
        protected $_table;

        /**
         * Db constructor.
         *
         * @param string $table
         */
        public function __construct($table = 'manaphp_counter')
        {
            $this->_table = $table;
        }

        /**
         * @param string $key
         * @param int    $step
         *
         * @return int
         * @throws \ManaPHP\Counter\Exception
         */
        public function _increment($key, $step)
        {
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
            $this->db->delete($this->_table, ['name' => $key]);
        }
    }
}