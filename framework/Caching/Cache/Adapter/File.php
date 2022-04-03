<?php
declare(strict_types=1);

namespace ManaPHP\Caching\Cache\Adapter;

use ManaPHP\Caching\AbstractCache;
use ManaPHP\Exception\CreateDirectoryFailedException;
use ManaPHP\Exception\WriteFileFailedException;

/**
 * @property-read \ManaPHP\AliasInterface $alias
 */
class File extends AbstractCache
{
    protected string $dir;
    protected int $level;
    protected string $ext;

    public function __construct(string $dir = '@runtime/cache', int $level = 1, string $ext = '.cache')
    {
        $this->dir = rtrim($dir, '\\/');
        $this->level = $level;
        $this->ext = $ext;
    }

    protected function getFileName(string $key): string
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

    public function do_exists(string $key): bool
    {
        $file = $this->getFileName($key);

        return (@filemtime($file) >= time());
    }

    public function do_get(string $key): false|string
    {
        $file = $this->getFileName($key);

        if (@filemtime($file) >= time()) {
            return file_get_contents($file);
        } else {
            return false;
        }
    }

    public function do_set(string $key, string $value, int $ttl): void
    {
        $file = $this->getFileName($key);

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
    public function do_delete(string $key): void
    {
        $file = $this->getFileName($key);

        @unlink($file);
    }
}