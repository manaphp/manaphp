<?php

namespace ManaPHP\Cache\Adapter {

    use ManaPHP\Cache;

    class Apc extends Cache
    {
        /**
         * @var string
         */
        protected $_prefix = '_MANA_CACHE_';

        /**
         * Apc constructor.
         * @param array $options
         *
         * @throws \ManaPHP\Cache\Adapter\Exception
         */
        public function __construct($options = [])
        {
            if (!function_exists('apc_exists')) {
                throw new Exception('apc extension is not loaded: http://pecl.php.net/package/APCu');
            }

            if (!ini_get('apc.enable_cli')) {
                throw new Exception('apc.enable_cli=0, please enable it.');
            }

            if (isset($options['prefix'])) {
                $this->_prefix .= $options['prefix'];
            }
        }

        public function _exists($key)
        {
            return apc_exists($this->_prefix . $key);
        }

        public function _get($key)
        {
            return apc_fetch($this->_prefix . $key);
        }

        public function _set($key, $value, $ttl)
        {
            $r = apc_store($this->_prefix . $key, $value, $ttl);
            if (!$r) {
                throw new Exception('apc store failed: ' . $key);
            }
        }

        public function _delete($key)
        {
            apc_delete($this->_prefix . $key);
        }
    }
}