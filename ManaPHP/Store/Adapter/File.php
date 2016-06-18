<?php
namespace ManaPHP\Store\Adapter {

    use ManaPHP\Store;
    use ManaPHP\Utility\Text;

    class File extends Store
    {
        /**
         * @var string
         */
        protected $_storeDir = '@data/Stores';

        /**
         * @var string
         */
        protected $_extension = '.store';

        /**
         * @var int
         */
        protected $_dirLevel = 1;

        /**
         * @var array
         */

        /**
         * File constructor.
         *
         * @param string|array $options
         *
         * @throws \ManaPHP\Configure\Exception
         */
        public function __construct($options = [])
        {
            if (is_string($options)) {
                $options = ['storeDir' => $options];
            }

            if (isset($options['storeDir'])) {
                $this->_storeDir = rtrim($options['storeDir']);
            }
        }

        /**
         * @param string $key
         *
         * @return string
         */
        protected function _getFileName($key)
        {
            if ($key[0] === '!') {
                return $this->alias->resolve($this->_storeDir . '/' . substr($key, 1) . $this->_extension);
            }

            if (Text::contains($key, '/')) {
                list($prefix, $key) = explode('/', $key, 2);
                $dir = $this->_storeDir . '/' . $prefix;
            } else {
                $dir = $this->_storeDir;
            }
            $md5 = md5($key);

            for ($i = 0; $i < $this->_dirLevel; $i++) {
                $dir .= '/' . substr($md5, $i + $i, 2);
            }

            $dir .= '/' . $md5 . $this->_extension;

            return $this->alias->resolve($dir);
        }

        public function _exists($id)
        {
            $storeFile = $this->_getFileName($id);

            return is_file($storeFile);
        }

        public function _get($id)
        {
            $storeFile = $this->_getFileName($id);

            if (is_file($storeFile)) {
                return file_get_contents($storeFile);
            } else {
                return false;
            }
        }

        public function _mGet($ids)
        {
            $idValues = [];

            foreach ($ids as $id) {
                $idValues[$id] = $this->_get($id);
            }

            return $idValues;
        }

        public function _set($id, $value)
        {
            $storeFile = $this->_getFileName($id);

            $storeDir = dirname($storeFile);
            if (!@mkdir($storeDir, 0755, true) && !is_dir($storeDir)) {
                throw new Exception('Create store directory "' . $storeDir . '" failed: ' . error_get_last()['message']);
            }

            if (file_put_contents($storeFile, $value, LOCK_EX) === false) {
                throw new Exception('Write store file"' . $storeFile . '" failed: ' . error_get_last()['message']);
            }

            clearstatcache(true, $storeFile);
        }

        public function _mSet($idValues)
        {
            foreach ($idValues as $id => $value) {
                $this->_set($id, $value);
            }
        }

        public function _delete($id)
        {
            $storeFile = $this->_getFileName($id);

            @unlink($storeFile);
        }
    }
}