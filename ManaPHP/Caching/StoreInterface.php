<?php
namespace ManaPHP\Caching {

    interface StoreInterface
    {
        /**
         * Checks whether a specified id exists in the store.
         *
         * @param string $id
         *
         * @return bool
         */
        public function exists($id);

        /**
         * Retrieves a value from store with a specified id.
         *
         * @param string $id
         *
         * @return mixed|false
         */
        public function get($id);

        /**
         * Retrieves multiple values from store with corresponding ids.
         *
         * @param array $ids
         *
         * @return array
         */
        public function mGet($ids);

        /**
         * Stores a value identified by a id into store.
         *
         * @param string $id
         * @param mixed  $value
         *
         * @return void
         */
        public function set($id, $value);

        /**
         * Stores multiple values corresponding with ids into store.
         *
         * @param array $idValues
         *
         * @return void
         */
        public function mSet($idValues);

        /**
         * Deletes a value with the specified id from store
         *
         * @param string $id
         *
         * @void
         */
        public function delete($id);

        /** Retrieves the internal adapter instance
         *
         * @return \ManaPHP\Caching\Store\AdapterInterface
         */
        public function getAdapter();

        /**
         * @return \ManaPHP\Caching\Serializer\AdapterInterface
         */
        public function getSerializer();
    }
}