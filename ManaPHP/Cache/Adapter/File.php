<?php
namespace ManaPHP\Cache\Adapter {

    use ManaPHP\Cache;
    use ManaPHP\Utility\Text;

    class File extends Cache
    {
        /**
         * @var string
         */
        protected $_cacheDir = '@data/Cache';

        /**
         * @var string
         */
        protected $_extension = '.cache';

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
         * @throws \ManaPHP\Cache\Exception|\ManaPHP\Configure\Exception
         */
        public function __construct($options = [])
        {
            parent::__construct();

            if (is_string($options)) {
                $options = ['cacheDir' => $options];
            }

            if (isset($options['cacheDir'])) {
                $this->_cacheDir = rtrim($options['cacheDir'], '\\/');
            }

            if (isset($options['dirLevel'])) {
                $this->_dirLevel = $options['dirLevel'];
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
                return $this->alias->resolve($this->_cacheDir . '/' . substr($key, 1) . $this->_extension);
            }

            if (Text::contains($key, '/')) {
                list($prefix, $key) = explode('/', $key, 2);
                $dir = $this->_cacheDir . '/' . $prefix;
            } else {
                $dir = $this->_cacheDir;
            }
            $md5 = md5($key);

            for ($i = 0; $i < $this->_dirLevel; $i++) {
                $dir .= '/' . substr($md5, $i + $i, 2);
            }

            $dir .= '/' . $md5 . $this->_extension;

            return $this->alias->resolve($dir);
        }

        public function _exists($key)
        {
            $cacheFile = $this->_getFileName($key);

            return (@filemtime($cacheFile) >= time());
        }

        public function _get($key)
        {
            $cacheFile = $this->_getFileName($key);

            if (@filemtime($cacheFile) >= time()) {
                return file_get_contents($cacheFile);
            } else {
                return false;
            }
        }

        public function _set($key, $value, $ttl)
        {
            $cacheFile = $this->_getFileName($key);

            $cacheDir = dirname($cacheFile);
            if (!@mkdir($cacheDir, 0755, true) && !is_dir($cacheDir)) {
                throw new Exception('Create cache directory "' . $cacheDir . '" failed: ' . error_get_last()['message']);
            }

            if (file_put_contents($cacheFile, $value, LOCK_EX) === false) {
                throw new Exception('Write cache file"' . $cacheFile . '" failed: ' . error_get_last()['message']);
            }

            @touch($cacheFile, time() + $ttl);
            clearstatcache(true, $cacheFile);
        }

        public function _delete($key)
        {
            $cacheFile = $this->_getFileName($key);

            @unlink($cacheFile);
        }
    }
}