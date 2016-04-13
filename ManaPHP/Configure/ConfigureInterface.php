<?php
namespace ManaPHP\Configure {

    interface ConfigureInterface
    {

        /**
         * @param string $name
         * @param string $path
         *
         * @return static
         * @throws \ManaPHP\Configure\Exception
         */
        public function setAlias($name, $path);

        /**
         * @param string $name
         *
         * @return string|null
         */
        public function getAlias($name);

        /**
         * @param string $name
         *
         * @return bool
         */
        public function hasAlias($name);

        /**
         * @param string $path
         *
         * @return string
         * @throws \ManaPHP\Configure\Exception
         */
        public function resolvePath($path);
    }
}