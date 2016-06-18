<?php
namespace Application {
use ManaPHP\Utility\Text;

    class Configure extends \ManaPHP\Configure\Configure
    {

        /**
         * @var \ManaConfigure\Db\Adapter\Mysql $database
         */
        public $database;

        /**
         * @var \ManaConfigure\Log\Adapter\File
         */
        public $log;

        /**
         * @var \ManaConfigure\Security\Crypt
         */
        public $crypt;

        /**
         * @var \ManaConfigure\Debugger
         */
        public $debugger;

        public function __construct($dependencyInjector = null)
        {
            $this->config();
        }

        public function config()
        {
            $this->debug = true;

            $this->database = new \stdClass();
            $this->database->host = 'localhost';
            $this->database->port = 3306;
            $this->database->username = 'root';
            $this->database->password = '';
            $this->database->dbname = 'manaphp_unit_test';
            $this->database->options = [\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'"];

            $this->log = new \stdClass();
            $this->log->file = $this->alias->resolve('@data/Logs/' . date('Ymd') . '.log');

            $this->crypt = new \stdClass();
            $this->crypt->key = 'test';

            $this->debugger = new \stdClass();
            $this->debugger->disableAutoResponse = (!Text::startsWith($_SERVER['REMOTE_ADDR'], '127.0.0.') && !Text::startsWith($_SERVER['REMOTE_ADDR'], '192.168.'));
        }
    }
}