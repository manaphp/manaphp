<?php
namespace ManaPHP\Cache\Adapter;

use ManaPHP\Cache;
use ManaPHP\Di;

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
     * File constructor.
     *
     * @param string|array|\ConfManaPHP\Cache\Adapter\File $options
     *
     * @throws \ManaPHP\Cache\Exception|\ManaPHP\Configure\Exception
     */
    public function __construct($options = [])
    {
        if (is_object($options)) {
            $options = (array)$options;
        }

        if (is_string($options)) {
            $options = ['cacheDir' => $options];
        }

        if (isset($options['cacheDir'])) {
            $this->_cacheDir = rtrim($options['cacheDir'], '\\/');
        }

        if (isset($options['dirLevel'])) {
            $this->_dirLevel = $options['dirLevel'];
        }

        if (isset($options['extension'])) {
            $this->_extension = $options['extension'];
        }

        if (isset($options['serializer'])) {
            $this->serializer = Di::getDefault()->getShared($options['serializer']);
        }
    }

    /**
     * @param string $key
     *
     * @return string
     */
    protected function _getFileName($key)
    {
        $pos = strrpos($key, '/');

        if ($pos !== false && strlen($key) - $pos - 1 === 32) {
            $prefix = substr($key, 0, $pos);
            $md5 = substr($key, $pos + 1);
            $shard = '';

            for ($i = 0; $i < $this->_dirLevel; $i++) {
                $shard .= '/' . substr($md5, $i + $i, 2);
            }
            $key = $prefix . $shard . '/' . $md5;
        }

        if ($key[0] !== '/') {
            $key = '/' . $key;
        }

        return $this->alias->resolve($this->_cacheDir . $key . $this->_extension);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function _exists($key)
    {
        $cacheFile = $this->_getFileName($key);

        return (@filemtime($cacheFile) >= time());
    }

    /**
     * @param string $key
     *
     * @return string|false
     */
    public function _get($key)
    {
        $cacheFile = $this->_getFileName($key);

        if (@filemtime($cacheFile) >= time()) {
            return file_get_contents($cacheFile);
        } else {
            return false;
        }
    }

    /**
     * @param string $key
     * @param string $value
     * @param int    $ttl
     *
     * @return void
     * @throws \ManaPHP\Cache\Adapter\Exception
     */
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

    /**
     * @param string $key
     *
     * @return void
     */
    public function _delete($key)
    {
        $cacheFile = $this->_getFileName($key);

        @unlink($cacheFile);
    }
}