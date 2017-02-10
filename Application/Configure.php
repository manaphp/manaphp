<?php
namespace Application;

use ManaPHP\Db\Adapter\Mysql;

class Configure extends \ManaPHP\Configure
{
    public function __construct()
    {
        $this->config();
    }

    public function config()
    {
        $this->debug = true;

        /*
         * --------------------------------------------------------------------------
         *  Encryption Key
         * --------------------------------------------------------------------------
         *
         *  This key should be set to a random, 32 character string, otherwise these encrypted strings
         *  will not be safe. Please do this before deploying an application!
         *
         */
        $this->_masterKey = 'key';

        $this->db = new \stdClass();
        $this->db->adapter = Mysql::class;
        $this->db->host = 'localhost';
        $this->db->port = 3306;
        $this->db->username = 'root';
        $this->db->password = '';
        $this->db->dbname = 'manaphp';
        $this->db->options = [\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'"];

        $this->logger = new \stdClass();
        $this->logger->file = '@data/logger/' . date('Ymd') . '.log';

        $this->redis = new \stdClass();
        $this->redis->host = 'localhost';

        $this->modules = ['Home' => '/', 'Admin' => '/admin', 'Api' => '/api'];
    }
}