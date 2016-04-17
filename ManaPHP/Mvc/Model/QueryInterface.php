<?php

namespace ManaPHP\Mvc\Model {

    /**
     * ManaPHP\Mvc\Model\QueryInterface initializer
     */
    interface QueryInterface
    {
        /**
         * Executes the sql query statement
         *
         * @param array $bind
         *
         * @return array
         */
        public function execute($bind = null);

        /**
         * Set default bind parameters
         *
         * @param array   $bind
         * @param boolean $merge
         *
         * @return static
         */
        public function setBind($bind, $merge = false);

        /**
         * Sets the cache parameters of the query
         *
         * @param array $options
         *
         * @return static
         */
        public function cache($options);
    }
}
