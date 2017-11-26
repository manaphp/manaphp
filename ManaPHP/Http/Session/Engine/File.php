<?php
namespace ManaPHP\Http\Session\Engine;

use ManaPHP\Component;
use ManaPHP\Http\Session\Engine\Exception as SessionException;
use ManaPHP\Http\Session\EngineInterface;

/**
 * Class ManaPHP\Http\Session\Engine\File
 *
 * @package session\engine
 */
class File extends Component implements EngineInterface
{
    /**
     * @var string
     */
    protected $_dir = '@data/session';

    /**
     * @var string
     */
    protected $_extension = '.session';

    /**
     * @var int
     */
    protected $_level = 1;

    /**
     * File constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (is_object($options)) {
            $options = (array)$options;
        }

        if (isset($options['dir'])) {
            $this->_dir = ltrim($options['dir'], '\\/');
        }

        if (isset($options['extension'])) {
            $this->_extension = $options['extension'];
        }

        if (isset($options['level'])) {
            $this->_level = $options['level'];
        }
    }

    /**
     * @param string $sessionId
     *
     * @return string
     */
    protected function _getFileName($sessionId)
    {
        $shard = '';

        for ($i = 0; $i < $this->_level; $i++) {
            $shard .= '/' . substr($sessionId, $i + $i, 2);
        }

        return $this->alias->resolve($this->_dir . $shard . '/' . $sessionId . $this->_extension);
    }

    /**
     * @param string $session_id
     *
     * @return string
     */
    public function read($session_id)
    {
        $file = $this->_getFileName($session_id);

        if (file_exists($file) && filemtime($file) >= time()) {
            return file_get_contents($file);
        } else {
            return '';
        }
    }

    /**
     * @param string $session_id
     * @param string $data
     * @param array  $context
     *
     * @return bool
     *
     * @throws \ManaPHP\Http\Session\Exception
     */
    public function write($session_id, $data, $context)
    {
        $file = $this->_getFileName($session_id);
        $dir = dirname($file);
        if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new SessionException('create `:dir` session directory failed: :last_error_message'/**m0842502d4c2904242*/, ['dir' => $dir]);
        }

        if (file_put_contents($file, $data, LOCK_EX) === false) {
            trigger_error(strtr('write `:file` session file failed: :last_error_message'/**m0f7ee56f71e1ec344*/, [':file' => $file]));
        }

        @touch($file, time() + $context['ttl']);
        clearstatcache(true, $file);

        return true;
    }

    /**
     * @param string $session_id
     *
     * @return bool
     */
    public function destroy($session_id)
    {
        $file = $this->_getFileName($session_id);

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
    public function gc($ttl)
    {
        $dir = $this->alias->resolve($this->_dir);
        if (is_dir($dir)) {
            $this->_clean($dir);
        }

        return true;
    }

    /**
     * @param string $dir
     *
     * @return void
     */
    protected function _clean($dir)
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
                $this->_clean($path);
            }
        }
    }
}