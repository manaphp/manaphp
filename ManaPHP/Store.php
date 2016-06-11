<?php
namespace ManaPHP {

    use ManaPHP\Di;
    use ManaPHP\Store\AdapterInterface;

    abstract class Store extends Component implements StoreInterface, AdapterInterface
    {
        /**
         * Fetch content
         *
         * @param string $id
         *
         * @return mixed
         * @throws \ManaPHP\Store\Exception
         */
        public function get($id)
        {
            $content = $this->_get($id);
            if ($content === false) {
                return false;
            }

            return $this->serializer->deserialize($content);
        }

        /**
         * Retrieves a value from store with a specified id.
         *
         * @param array $ids
         *
         * @return array
         * @throws \ManaPHP\Store\Exception
         */
        public function mGet($ids)
        {
            $idValues = [];
            foreach ($ids as $id) {
                $value = $this->_get($id);
                if ($value === false) {
                    $idValues[$id] = $value;
                } else {
                    $idValues[$id] = $this->serializer->deserialize($value);
                }
            }

            return $idValues;
        }

        /**
         * Stores content
         *
         * @param string $id
         * @param mixed  $value
         *
         * @return void
         * @throws \ManaPHP\Cache\Exception
         */
        public function set($id, $value)
        {
            $this->_set($id, $this->serializer->serialize($value));
        }

        /**
         * Stores a value identified by a id into store.
         *
         * @param array $idValues
         *
         * @return void
         */
        public function mSet($idValues)
        {
            $completeIdValues = [];
            foreach ($idValues as $id => $value) {
                $completeIdValues[$id] = $this->serializer->serialize($value);
            }

            $this->_mSet($completeIdValues);
        }

        /**
         * Delete content
         *
         * @param string $id
         *
         * @void
         */
        public function delete($id)
        {
            $this->_delete($id);
        }

        /**
         * Deletes values with the specified ids from store
         *
         * @param array $ids
         *
         * @void
         */
        public function mDelete($ids)
        {
            foreach ($ids as $id) {
                $this->_delete($id);
            }
        }

        /**
         * Check if id exists
         *
         * @param string $id
         *
         * @return bool
         */
        public function exists($id)
        {
            return $this->_exists($id);
        }

        /**
         * @return array
         */
        public function __debugInfo()
        {
            return get_object_vars($this) ?: [];
        }
    }
}