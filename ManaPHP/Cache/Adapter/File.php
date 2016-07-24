<?php
namespace ManaPHP\Cache\Adapter;

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
    }

    /**
     * @param string $key
     *
     * @return string
     */
    protected function _getFileName($key)
    {
        if ($key[0] === '!') {
            return $this->alias->resolve($this->_cacheDir . '/' . str_replace(':', '/', substr($key, 1)) . $this->_extension);
        }

        if (Text::contains($key, '/')) {
            $parts = explode('/', $key, 2);
            $md5 = $parts[1];
            $file = $this->_cacheDir . '/' . $parts[0] . '/';

            for ($i = 0; $i < $this->_dirLevel; $i++) {
                $file .= substr($md5, $i + $i, 2) . '/';
            }
            $file .= $md5;
        } else {
            $file = $this->_cacheDir . '/' . $key;
        }

        return $this->alias->resolve(str_replace(':', '/', $file . $this->_extension));
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