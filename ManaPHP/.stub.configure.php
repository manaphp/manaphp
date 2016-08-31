<?php

namespace ConfManaPHP\Db\Adapter {

    class Mysql
    {
        /**
         * @var string
         */
        public $host;

        /**
         * @var int
         */
        public $port;

        /**
         * @var string
         */
        public $dbname;

        /**
         * @var string
         */
        public $username;

        /**
         * @var string
         */
        public $password;

        /**
         * @var array
         */
        public $options;

        /**
         * @var string
         */
        public $dsn;
    }
}

namespace ConfManaPHP\Security {

    class Crypt
    {
        public $key;
    }
}

namespace ConfManaPHP {

    class Debugger
    {
        public $autoResponse;
    }
}
namespace ConfManaPHP\Cache\Adapter {

    class Apc
    {
        /**
         * @var string
         */
        public $prefix;
    }

    class File
    {
        /**
         * @var string
         */
        public $dir;

        /**
         * @var int
         */
        public $level;

        /**
         * @var string
         */
        public $extension;
    }

    class Redis
    {
        /**
         * @var string
         */
        public $prefix;
    }
}

namespace ConfManaPHP\Store\Adapter {

    class Redis
    {
        /**
         * @var string
         */
        public $prefix;
    }

    class File
    {
        /**
         * @var string
         */
        public $dir;

        /**
         * @var int
         */
        public $level;

        /**
         * @var string
         */
        public $extension;
    }
}
namespace ConfManaPHP\Counter\Adapter {

    class Db
    {
        /**
         * @var string
         */
        public $table;
    }
}

namespace ConfManaPHP\Logger\Adapter {

    class File
    {
        /**
         * @var string
         */
        public $file;

        /**
         * @var string
         */
        public $format;
    }
}

namespace ConfManaPHP\Security {

    class Captcha
    {
        /**
         * @var string
         */
        public $charset;

        /**
         * @var array
         */
        public $fonts;

        /**
         * @var int
         */
        public $length;

        /**
         * @var string
         */
        public $bgRGB;
    }
}