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
         * @param array $binds
         *
         * @return array
         */
        public function execute($binds = null);

        /**
         * Set default bind parameters
         *
         * @param array   $binds
         * @param boolean $merge
         *
         * @return static
         */
        public function setBinds($binds, $merge = false);

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
