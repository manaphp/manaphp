<?php
namespace Application {

    class Configure extends \ManaPHP\Configure\Configure
    {

        /**
         * @var \Configure\Db\Adapter\Mysql $database
         */
        public $database;

        /**
         * @var \Configure\Log\Adapter\File
         */
        public $log;

        public function __construct($dependencyInjector = null)
        {
            parent::__construct($dependencyInjector);

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

            $this->log = new \stdClass();
            $this->log->file = $this->resolvePath('@data/Logs') . date('Ymd') . '.log';
        }
    }
}