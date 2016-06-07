<?php

namespace ManaPHP {

    class Autoloader
    {
        /**
         * @var string
         */
        protected static $_rootPath;

        public static function ___autoload($className)
        {
            if (strpos($className, 'ManaPHP') !== 0) {
                return false;
            }

            if (self::$_rootPath === null) {
                self::$_rootPath = str_replace('\\', '/', dirname(__DIR__));
            }

            $file = self::$_rootPath . '/' . str_replace('\\', '/', $className) . '.php';
            if (is_file($file)) {
                if (PHP_EOL !=="\n" && str_replace('\\', '/', realpath($file)) !== $file) {
                    trigger_error('File name case mismatch for ' . $file, E_USER_ERROR);
                }

                /** @noinspection PhpIncludeInspection */
                require $file;

                return true;
            }

            return false;
        }

        /**
         * @return bool
         */
        public static function register()
        {
            return spl_autoload_register([__CLASS__, '___autoload']);
        }
    }
}
