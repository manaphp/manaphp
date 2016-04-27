<?php

namespace Configure\Db\Adapter {

    class Mysql
    {
        public $host;
        public $username;
        public $password;
        public $dbname;
        public $port;
        public $options;
    }
}

namespace Configure\Log\Adapter {

    class File
    {
        public $file;
    }
}

namespace Configure\Security{
    class Crypt{
        public $key;
    }
}