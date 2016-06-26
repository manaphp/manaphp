<?php
namespace ManaPHP\Http {

    interface FilterInterface
    {

        /**
         * @param string   $name
         * @param callable $method
         *
         * @return mixed
         */
        public function add($name, $method);

        /**
         * @param string $attribute
         * @param string $rule
         * @param string $value
         *
         * @return mixed
         */
        public function sanitize($attribute, $rule, $value);
    }
}