<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/20
 * Time: 22:06
 */
namespace ManaPHP\Db\Adapter {

    use ManaPHP\Db;

    class Mysql extends Db
    {
        /**
         * \ManaPHP\Db\Adapter constructor
         *
         * @param array $descriptor
         */
        public function __construct($descriptor)
        {
            $this->_type = 'mysql';

            if (is_object($descriptor)) {
                $descriptor = (array)$descriptor;
            }

            /** @noinspection AdditionOperationOnArraysInspection */
            $descriptor += ['host' => 'localhost', 'port' => 3306, 'username' => 'root', 'password' => '', 'options' => []];

            if (!isset($descriptor['options'][\PDO::MYSQL_ATTR_INIT_COMMAND])) {
                $descriptor['options'][\PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES 'UTF8'";
            }

            parent::__construct($descriptor);
        }
    }
}