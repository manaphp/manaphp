<?php

namespace ManaPHP\Http\Session {

    /**
     * ManaPHP\Http\Session\BagInterface initializer
     */
    interface BagInterface
    {
        /**
         * Destroy the session bag
         */
        public function destroy();

        /**
         * Setter of values
         *
         * @param string $property
         * @param string $value
         */
        public function set($property, $value);

        /**
         * Getter of values
         *
         * @param string $property
         * @param mixed  $defaultValue
         *
         * @return mixed
         */
        public function get($property, $defaultValue = null);

        /**
         * Isset property
         *
         * @param string $property
         *
         * @return boolean
         */
        public function has($property);

        /**
         * Unset property
         *
         * @param string $property
         */
        public function remove($property);

    }
}
