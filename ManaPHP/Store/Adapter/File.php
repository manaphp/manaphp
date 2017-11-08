<?php
namespace ManaPHP\Store\Adapter;

use ManaPHP\Component;
use ManaPHP\Store\Adapter\File\Exception as FileException;
use ManaPHP\Store\AdapterInterface;
use ManaPHP\Utility\Text;

/**
 * Class ManaPHP\Store\Adapter\File
 *
 * @package store\adapter
 */
class File extends Component implements AdapterInterface
{
    /**
     * @var string
     */
    protected $_dir = '@data/store';

    /**
     * @var string
     */
    protected $_extension = '.store';

    /**
     * @var int
     */
    protected $_level = 1;

    /**
     * File constructor.
     *
     * @param string|array|\ConfManaPHP\Store\Adapter\File $options
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

        if (isset($options['extension'])) {
            $this->_extension = $options['extension'];
        }

        if (isset($options['level'])) {
            $this->_level = $options['level'];
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
            return $this->alias->resolve($this->_dir . '/' . strtr(substr($key, 1), ':', '/') . $this->_extension);
        }

        if (Text::contains($key, '/')) {
            $parts = explode('/', $key, 2);
            $md5 = $parts[1];
            $file = $this->_dir . '/' . $parts[0] . '/';

            for ($i = 0; $i < $this->_level; $i++) {
                $file .= substr($md5, $i + $i, 2) . '/';
            }
            $file .= $md5;
        } else {
            $file = $this->_dir . '/' . $key;
        }

        return $this->alias->resolve(strtr($file, ':', '/') . $this->_extension);
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
     * @throws \ManaPHP\Store\Adapter\Exception
     */
    public function set($id, $value)
    {
        $file = $this->_getFileName($id);

        $dir = dirname($file);
        if (!@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new FileException('Create `dir` store directory failed: :last_error_message'/**m0152cd058643d24d6*/, ['dir' => $dir]);
        }

        if (file_put_contents($file, $value, LOCK_EX) === false) {
            throw new FileException('write store `:file` file failed: :last_error_message'/**m0d7c8cf410b1e3a68*/, ['file' => $file]);
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