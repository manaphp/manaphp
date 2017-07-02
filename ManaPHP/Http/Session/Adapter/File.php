<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/12/19
 * Time: 17:20
 */
namespace ManaPHP\Http\Session\Adapter;

use ManaPHP\Component;
use ManaPHP\Http\Session\Adapter\Exception as SessionException;
use ManaPHP\Http\Session\AdapterInterface;

/**
 * Class ManaPHP\Http\Session\Adapter\File
 *
 * @package session\adapter
 */
class File extends Component implements AdapterInterface
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
     * @param string $savePath
     * @param string $sessionName
     *
     * @return bool
     */
    public function open($savePath, $sessionName)
    {
        return true;
    }

    /**
     * @return bool
     */
    public function close()
    {
        return true;
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
     * @param string $sessionId
     *
     * @return string
     */
    public function read($sessionId)
    {
        $file = $this->_getFileName($sessionId);

        if (file_exists($file) && filemtime($file) >= time()) {
            return file_get_contents($file);
        } else {
            return '';
        }
    }

    /**
     * @param string $sessionId
     * @param string $data
     *
     * @return bool
     *
     * @throws \ManaPHP\Http\Session\Exception
     */
    public function write($sessionId, $data)
    {
        $file = $this->_getFileName($sessionId);
        $dir = dirname($file);
        if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new SessionException('create `:dir` session directory failed: :last_error_message'/**m0842502d4c2904242*/, ['dir' => $dir]);
        }

        if (file_put_contents($file, $data, LOCK_EX) === false) {
            trigger_error(strtr('write `:file` session file failed: :last_error_message'/**m0f7ee56f71e1ec344*/, [':file' => $file]));
        }

        /** @noinspection UsageOfSilenceOperatorInspection */
        @touch($file, time() + ini_get('session.gc_maxlifetime'));
        clearstatcache(true, $file);

        return true;
    }

    /**
     * @param string $sessionId
     *
     * @return bool
     */
    public function destroy($sessionId)
    {
        $file = $this->_getFileName($sessionId);

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
        $this->clean();

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

    /**
     * @return void
     */
    public function clean()
    {
        $dir = $this->alias->resolve($this->_dir);
        if (is_dir($dir)) {
            $this->_clean($dir);
        }
    }
}