<?php
namespace ManaPHP\Store\Engine;

use ManaPHP\Component;
use ManaPHP\Exception\CreateDirectoryFailedException;
use ManaPHP\Exception\RuntimeException;
use ManaPHP\Store\EngineInterface;

/**
 * Class ManaPHP\Store\Engine\File
 *
 * @package store\engine
 */
class File extends Component implements EngineInterface
{
    /**
     * @var string
     */
    protected $_dir = '@data/store';

    /**
     * @var int
     */
    protected $_level = 1;

    /**
     * @var string
     */
    protected $_ext = '.store';

    /**
     * File constructor.
     *
     * @param string|array $options
     */
    public function __construct($options = [])
    {
        if (is_string($options)) {
            $options = ['dir' => $options];
        }

        if (isset($options['dir'])) {
            $this->_dir = rtrim($options['dir'], '\\/');
        }

        if (isset($options['level'])) {
            $this->_level = (int)$options['level'];
        }

        if (isset($options['ext'])) {
            $this->_ext = $options['ext'];
        }
    }

    /**
     * @param string $key
     *
     * @return string
     */
    protected function _getFileName($key)
    {
        $key = strtr($key, ':', '/');
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

        return $this->alias->resolve($this->_dir . $key . $this->_ext);
    }

    /**
     * @param string $id
     *
     * @return bool
     */
    public function exists($id)
    {
        $file = $this->_getFileName($id);

        return is_file($file);
    }

    /**
     * @param string $id
     *
     * @return false|string
     */
    public function get($id)
    {
        $file = $this->_getFileName($id);

        if (is_file($file)) {
            return file_get_contents($file);
        } else {
            return false;
        }
    }

    /**
     * @param string $id
     * @param string $value
     *
     * @return void
     */
    public function set($id, $value)
    {
        $file = $this->_getFileName($id);

        $dir = dirname($file);
        if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new CreateDirectoryFailedException(['Create `dir` store directory failed: :last_error_message'/**m0152cd058643d24d6*/, 'dir' => $dir]);
        }

        if (file_put_contents($file, $value, LOCK_EX) === false) {
            throw new RuntimeException(['write `:file` store file failed: :last_error_message'/**m0d7c8cf410b1e3a68*/, 'file' => $file]);
        }

        clearstatcache(true, $file);
    }

    /**
     * @param string $id
     *
     * @return void
     */
    public function delete($id)
    {
        $file = $this->_getFileName($id);

        @unlink($file);
    }
}