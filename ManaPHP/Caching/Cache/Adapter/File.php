<?php
namespace ManaPHP\Caching\Cache\Adapter {

    use ManaPHP\Caching\Cache\AdapterInterface;

    class File implements AdapterInterface
    {

        /**
         * @var string
         */
        protected $_cacheDir;

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
         * @param string $cacheDir
         * @param string $shardMode
         *
         * @throws \ManaPHP\Caching\Cache\Exception
         */
        public function __construct($cacheDir, $shardMode = null)
        {
            $this->_cacheDir = $cacheDir;
            $this->_shardMode = $shardMode;
        }

        /**
         * @param string $key
         *
         * @return string
         */
        protected function _getFileName($key)
        {
            return $this->_cacheDir . '/' . $key . $this->_extension;
        }

        public function exists($key)
        {
            $cacheFile = $this->_getFileName($key);

            return (@filemtime($cacheFile) >= time());
        }

        public function get($key)
        {
            $cacheFile = $this->_getFileName($key);

            if (@filemtime($cacheFile) >= time()) {
                return file_get_contents($cacheFile);
            } else {
                return false;
            }
        }

        public function set($key, $value, $ttl)
        {
            $cacheFile = $this->_getFileName($key);

            $cacheDir = dirname($cacheFile);
            if (@mkdir($cacheDir, 0755, true) && !is_dir($cacheDir)) {
                throw new Exception('Create cache directory "' . $cacheDir . '" failed: ' . error_get_last()['message']);
            }

            if (file_put_contents($cacheFile, $value, LOCK_EX) === false) {
                throw new Exception('Write cache file"' . $cacheFile . '" failed: ' . error_get_last()['message']);
            }

            @touch($cacheFile, time() + $ttl);
            clearstatcache(true, $cacheFile);
        }

        public function delete($key)
        {
            $cacheFile = $this->_getFileName($key);

            @unlink($cacheFile);
        }
    }
}