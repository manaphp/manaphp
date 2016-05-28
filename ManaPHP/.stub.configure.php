<?php

namespace ManaConfigure\Db\Adapter {

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

namespace ManaConfigure\Log\Adapter {

    class File
    {
        public $file;
    }
}

namespace ManaConfigure\Security {

    class Crypt
    {
        public $key;
    }
}

namespace ManaConfigure {

    class Debugger
    {
        public $disableAutoResponse;
    }
}