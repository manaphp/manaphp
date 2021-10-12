<?php

namespace ManaPHP\Http\Session\Adapter;

use ManaPHP\Exception\CreateDirectoryFailedException;
use ManaPHP\Http\AbstractSession;

/**
 * @property-read \ManaPHP\AliasInterface $alias
 */
class File extends AbstractSession
{
    /**
     * @var string
     */
    protected $dir = '@data/session';

    /**
     * @var string
     */
    protected $extension = '.session';

    /**
     * @var int
     */
    protected $level = 1;

    /**
     * @param array $options
     */
    public function __construct($options = [])
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

    /**
     * @param string $sessionId
     *
     * @return string
     */
    protected function getFileName($sessionId)
    {
        $shard = '';

        for ($i = 0; $i < $this->level; $i++) {
            $shard .= '/' . substr($sessionId, $i + $i, 2);
        }

        return $this->alias->resolve($this->dir . $shard . '/' . $sessionId . $this->extension);
    }

    /**
     * @param string $session_id
     *
     * @return string
     */
    public function do_read($session_id)
    {
        $file = $this->getFileName($session_id);

        if (file_exists($file) && filemtime($file) >= time()) {
            return file_get_contents($file);
        } else {
            return '';
        }
    }

    /**
     * @param string $session_id
     * @param string $data
     * @param int    $ttl
     *
     * @return bool
     */
    public function do_write($session_id, $data, $ttl)
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
    public function do_touch($session_id, $ttl)
    {
        $file = $this->getFileName($session_id);

        @touch($file, time() + $ttl);
        clearstatcache(true, $file);

        return true;
    }

    /**
     * @param string $session_id
     *
     * @return bool
     */
    public function do_destroy($session_id)
    {
        $file = $this->getFileName($session_id);

        if (file_exists($file)) {
            @unlink($file);
        }

        return true;
    }

    /**
     * @param int $ttl
     *
     * @return bool
     */
    public function do_gc($ttl)
    {
        $dir = $this->alias->resolve($this->dir);
        if (is_dir($dir)) {
            $this->clean($dir);
        }

        return true;
    }

    /**
     * @param string $dir
     *
     * @return void
     */
    protected function clean($dir)
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