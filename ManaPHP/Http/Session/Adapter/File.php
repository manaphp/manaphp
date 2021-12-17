<?php
declare(strict_types=1);

namespace ManaPHP\Http\Session\Adapter;

use ManaPHP\Exception\CreateDirectoryFailedException;
use ManaPHP\Http\AbstractSession;

/**
 * @property-read \ManaPHP\AliasInterface $alias
 */
class File extends AbstractSession
{
    protected string $dir = '@data/session';
    protected string $extension = '.session';
    protected int $level = 1;

    public function __construct(array $options = [])
    {
        parent::__construct($options);

        if (isset($options['dir'])) {
            $this->dir = ltrim($options['dir'], '\\/');
        }

        if (isset($options['extension'])) {
            $this->extension = $options['extension'];
        }

        if (isset($options['level'])) {
            $this->level = $options['level'];
        }
    }

    protected function getFileName(string $sessionId): string
    {
        $shard = '';

        for ($i = 0; $i < $this->level; $i++) {
            $shard .= '/' . substr($sessionId, $i + $i, 2);
        }

        return $this->alias->resolve($this->dir . $shard . '/' . $sessionId . $this->extension);
    }

    public function do_read(string $session_id): string
    {
        $file = $this->getFileName($session_id);

        if (file_exists($file) && filemtime($file) >= time()) {
            return file_get_contents($file);
        } else {
            return '';
        }
    }

    public function do_write(string $session_id, string $data, int $ttl): bool
    {
        $file = $this->getFileName($session_id);
        $dir = dirname($file);
        if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new CreateDirectoryFailedException($dir);
        }

        if (file_put_contents($file, $data, LOCK_EX) === false) {
            $error = error_get_last()['message'] ?? '';
            trigger_error(sprintf('write `%s` session file failed: %s', $file, $error));
        }

        @touch($file, time() + $ttl);
        clearstatcache(true, $file);

        return true;
    }

    /**
     * @param string $session_id
     * @param int    $ttl
     *
     * @return bool
     */
    public function do_touch(string $session_id, int $ttl): bool
    {
        $file = $this->getFileName($session_id);

        @touch($file, time() + $ttl);
        clearstatcache(true, $file);

        return true;
    }

    public function do_destroy(string $session_id): void
    {
        $file = $this->getFileName($session_id);

        if (file_exists($file)) {
            @unlink($file);
        }
    }

    public function do_gc(int $ttl): void
    {
        $dir = $this->alias->resolve($this->dir);
        if (is_dir($dir)) {
            $this->clean($dir);
        }
    }

    protected function clean(string $dir): void
    {
        foreach (scandir($dir, SCANDIR_SORT_ASCENDING) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;
            if (is_file($path)) {
                if (time() > filemtime($path)) {
                    @unlink($path);
                }
            } else {
                $this->clean($path);
            }
        }
    }
}