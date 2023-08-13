<?php
declare(strict_types=1);

namespace ManaPHP\Http\Session\Adapter;

use ManaPHP\AliasInterface;
use ManaPHP\Di\Attribute\Inject;
use ManaPHP\Exception\CreateDirectoryFailedException;
use ManaPHP\Http\AbstractSession;

class File extends AbstractSession
{
    #[Inject]
    protected AliasInterface $alias;

    protected string $dir;
    protected string $extension;
    protected int $level;

    public function __construct(
        string $dir = '@runtime/session', string $extension = '.session', int $level = 1,
        int $ttl = 3600, int $lazy = 60, string $name = "PHPSESSID",
        string $serializer = 'json', array $params = []
    ) {
        parent::__construct($ttl, $lazy, $name, $serializer, $params);

        $this->dir = ltrim($dir, '\\/');
        $this->extension = $extension;
        $this->level = $level;
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