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
         * @param array $options
         */
        public function __construct($options)
        {
            $this->_type = 'mysql';

            if (is_object($options)) {
                $options = (array)$options;
            }

            /** @noinspection AdditionOperationOnArraysInspection */
            $options += ['host' => 'localhost', 'port' => 3306, 'username' => 'root', 'password' => '', 'options' => []];

            if (!isset($options['options'][\PDO::MYSQL_ATTR_INIT_COMMAND])) {
                $options['options'][\PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES 'UTF8'";
            }

            parent::__construct($options);
        }
    }
}