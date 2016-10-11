<?php
namespace Application {

    use ManaPHP\Utility\Text;

    class Configure extends \ManaPHP\Configure\Configure
    {
        /**
         * @var \ConfManaPHP\Db\Adapter\Mysql $database
         */
        public $database;

        /**
         * @var \ConfManaPHP\Logger\Adapter\File
         */
        public $logger;

        /**
         * @var \ConfManaPHP\Security\Crypt
         */
        public $crypt;

        /**
         * @var \ConfManaPHP\Debugger
         */
        public $debugger;

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

            $this->database = new \stdClass();
            $this->database->host = 'localhost';
            $this->database->port = 3306;
            $this->database->username = 'root';
            $this->database->password = '';
            $this->database->dbname = 'manaphp_unit_test';
            $this->database->options = [\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'"];

            $this->logger = new \stdClass();
            $this->logger->file = '@data/logger/' . date('Ymd') . '.log';

            $this->debugger = new \stdClass();

            if (PHP_SAPI !== 'cli') {
                $this->debugger->autoResponse = $_SERVER['REMOTE_ADDR'] === $_SERVER['SERVER_ADDR'] || Text::startsWith($_SERVER['REMOTE_ADDR'], '192.168.');
            } else {
                $this->debugger->autoResponse = false;
            }
        }
    }
}