<?php
namespace Application;

class Configure extends \ManaPHP\Configure
{
    public function __construct()
    {
        parent::__construct();

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

        $this->db = 'mysql://localhost/manaphp_unit_test';

        $this->logger = new \stdClass();
        $this->logger->file = '@data/logger/' . date('Ymd') . '.log';

        $this->redis = 'redis://localhost';
        $this->modules = ['Home' => '/', 'Admin' => '/admin', 'Api' => '/api'];
    }
}