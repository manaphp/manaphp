<?php
namespace ManaPHP\Cache\Adapter;

use ManaPHP\Cache\Adapter\File\Exception as FileException;
use ManaPHP\Cache\AdapterInterface;
use ManaPHP\Component;

/**
 * Class ManaPHP\Cache\Adapter\File
 *
 * @package cache\adapter
 */
class File extends Component implements AdapterInterface
{
    /**
     * @var string
     */
    protected $_dir = '@data/cache';

    /**
     * @var string
     */
    protected $_extension = '.cache';

    /**
     * @var int
     */
    protected $_level = 1;

    /**
     * File constructor.
     *
     * @param string|array|\ConfManaPHP\Cache\Adapter\File $options
     *
     */
    public function __construct($options = [])
    {
        if (is_object($options)) {
            $options = (array)$options;
        } elseif (is_string($options)) {
            $options = ['dir' => $options];
        }

        if (isset($options['dir'])) {
            $this->_dir = rtrim($options['dir'], '\\/');
        }

        if (isset($options['level'])) {
            $this->_level = $options['level'];
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
        $key = str_replace(':', '/', $key);
        $pos = strrpos($key, '/');

        if ($pos !== false && strlen($key) - $pos - 1 === 32) {
            $prefix = substr($key, 0, $pos);
            $md5 = substr($key, $pos + 1);
            $shard = '';

            for ($i = 0; $i < $this->_level; $i++) {
                $shard .= '/' . substr($md5, $i + $i, 2);
            }
            $key = $prefix . $shard . '/' . $md5;
        }

        if ($key[0] !== '/') {
            $key = '/' . $key;
        }

        return $this->alias->resolve($this->_dir . $key . $this->_extension);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function exists($key)
    {
        $file = $this->_getFileName($key);

        return (@filemtime($file) >= time());
    }

    /**
     * @param string $key
     *
     * @return string|false
     */
    public function get($key)
    {
        $file = $this->_getFileName($key);

        if (@filemtime($file) >= time()) {
            return file_get_contents($file);
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
    public function set($key, $value, $ttl)
    {
        $file = $this->_getFileName($key);

        $dir = dirname($file);
        if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new FileException('create `:dir` cache directory failed: :last_error_message'/**m0842502d4c2904242*/, ['dir' => $dir]);
        }

        if (file_put_contents($file, $value, LOCK_EX) === false) {
            throw new FileException('write `:file` cache file failed: :last_error_message'/**m0f7ee56f71e1ec344*/, ['file' => $file]);
        }

        @touch($file, time() + $ttl);
        clearstatcache(true, $file);
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function delete($key)
    {
        $file = $this->_getFileName($key);

        @unlink($file);
    }
}