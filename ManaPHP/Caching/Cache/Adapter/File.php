<?php

namespace ManaPHP\Caching\Cache\Adapter;

use ManaPHP\Caching\Cache;
use ManaPHP\Exception\CreateDirectoryFailedException;
use ManaPHP\Exception\WriteFileFailedException;

/**
 * @property-read \ManaPHP\AliasInterface $alias
 */
class File extends Cache
{
    /**
     * @var string
     */
    protected $dir = '@data/cache';

    /**
     * @var int
     */
    protected $level = 1;

    /**
     * @var string
     */
    protected $ext = '.cache';

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['dir'])) {
            $this->dir = rtrim($options['dir'], '\\/');
        }

        if (isset($options['level'])) {
            $this->level = (int)$options['level'];
        }

        if (isset($options['ext'])) {
            $this->ext = $options['ext'];
        }
    }

    /**
     * @param string $key
     *
     * @return string
     */
    protected function getFileName($key)
    {
        $key = strtr($key, ':', '/');
        $pos = strrpos($key, '/');

        if ($pos !== false && strlen($key) - $pos - 1 === 32) {
            $prefix = substr($key, 0, $pos);
            $md5 = substr($key, $pos + 1);
            $shard = '';

            for ($i = 0; $i < $this->level; $i++) {
                $shard .= '/' . substr($md5, $i + $i, 2);
            }
            $key = $prefix . $shard . '/' . $md5;
        }

        if ($key[0] !== '/') {
            $key = '/' . $key;
        }

        return $this->alias->resolve($this->dir . $key . $this->ext);
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function do_exists($key)
    {
        $file = $this->self->getFileName($key);

        return (@filemtime($file) >= time());
    }

    /**
     * @param string $key
     *
     * @return string|false
     */
    public function do_get($key)
    {
        $file = $this->self->getFileName($key);

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
     */
    public function do_set($key, $value, $ttl)
    {
        $file = $this->self->getFileName($key);

        $dir = dirname($file);
        if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new CreateDirectoryFailedException($dir);
        }

        if (file_put_contents($file, $value, LOCK_EX) === false) {
            $error = error_get_last()['message'] ?? '';
            throw new WriteFileFailedException(['write `%s` cache file failed: %s', $file, $error]);
        }

        @touch($file, time() + $ttl);
        clearstatcache(true, $file);
    }

    /**
     * @param string $key
     *
     * @return void
     */
    public function do_delete($key)
    {
        $file = $this->self->getFileName($key);

        @unlink($file);
    }
}