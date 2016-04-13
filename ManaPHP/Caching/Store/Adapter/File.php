<?php
namespace ManaPHP\Caching\Store\Adapter {

    use ManaPHP\Caching\Store\AdapterInterface;

    class File implements AdapterInterface
    {
        /**
         * @var string
         */
        protected $_storeDir;

        /**
         * @var string
         */
        protected $_shardMode;

        /**
         * @var string
         */
        protected $_extension = '.cache';

        /**
         * @var array
         */

        /**
         * File constructor.
         *
         * @param string $storeDir
         * @param string $shardMode
         */
        public function __construct($storeDir, $shardMode = null)
        {
            $this->_storeDir = $storeDir;
            $this->_shardMode = $shardMode;
        }

        /**
         * @param string $id
         *
         * @return string
         */
        protected function _getFileName($id)
        {
            return $this->_storeDir . '/' . $id . $this->_extension;
        }

        public function exists($id)
        {
            $storeFile = $this->_getFileName($id);

            return is_file($storeFile);
        }

        public function get($id)
        {
            $storeFile = $this->_getFileName($id);

            if (is_file($storeFile)) {
                return file_get_contents($storeFile);
            } else {
                return false;
            }
        }

        public function mGet($ids)
        {
            $idValues = [];

            foreach ($ids as $id) {
                $idValues[$id] = $this->get($id);
            }

            return $idValues;
        }

        public function set($id, $value)
        {
            $storeFile = $this->_getFileName($id);

            $storeDir = dirname($storeFile);
            if (@mkdir($storeDir, 0755, true) && !is_dir($storeDir)) {
                throw new Exception('Create store directory "' . $storeDir . '" failed: ' . error_get_last()['message']);
            }

            if (file_put_contents($storeFile, $value, LOCK_EX) === false) {
                throw new Exception('Write store file"' . $storeFile . '" failed: ' . error_get_last()['message']);
            }

            clearstatcache(true, $storeFile);
        }

        public function mSet($idValues)
        {
            foreach ($idValues as $id => $value) {
                $this->set($id, $value);
            }
        }

        public function delete($id)
        {
            $storeFile = $this->_getFileName($id);

            @unlink($storeFile);
        }
    }
}