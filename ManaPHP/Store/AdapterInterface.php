<?php
namespace ManaPHP\Store {

    interface AdapterInterface
    {

        /**
         * Checks whether a specified id exists in the store.
         *
         * @param string $id
         *
         * @return bool
         */
        public function _exists($id);

        /**
         * Retrieves a value from store with a specified id.
         *
         * @param string $id
         *
         * @return string|false
         */
        public function _get($id);

        /**
         * Retrieves a value from store with a specified id.
         *
         * @param array $ids
         *
         * @return array
         */
        public function _mGet($ids);

        /**
         * Stores a value identified by a id into store.
         *
         * @param string $id
         * @param string $value
         *
         * @return void
         */
        public function _set($id, $value);

        /**
         * Stores a value identified by a id into store.
         *
         * @param array $idValues
         *
         * @return void
         */
        public function _mSet($idValues);

        /**
         * Deletes a value with the specified id from store
         *
         * @param string $id
         *
         * @void
         */
        public function _delete($id);
    }
}