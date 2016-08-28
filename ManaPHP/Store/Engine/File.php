<?php
namespace ManaPHP\Store\Engine;

use ManaPHP\Component;
use ManaPHP\Store\EngineInterface;
use ManaPHP\Utility\Text;

class File extends Component implements EngineInterface
{
    /**
     * @var string
     */
    protected $_storeDir = '@data/store';

    /**
     * @var string
     */
    protected $_extension = '.store';

    /**
     * @var int
     */
    protected $_dirLevel = 1;

    /**
     * File constructor.
     *
     * @param string|array|\ConfManaPHP\Store\Engine\File $options
     */
    public function __construct($options = [])
    {
        if (is_object($options)) {
            $options = (array)$options;
        }

        if (is_string($options)) {
            $options = ['storeDir' => $options];
        }

        if (isset($options['storeDir'])) {
            $this->_storeDir = rtrim($options['storeDir']);
        }

        if (isset($options['dirLevel'])) {
            $this->_dirLevel = $options['dirLevel'];
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
            return $this->alias->resolve($this->_storeDir . '/' . str_replace(':', '/', substr($key, 1)) . $this->_extension);
        }

        if (Text::contains($key, '/')) {
            $parts = explode('/', $key, 2);
            $md5 = $parts[1];
            $file = $this->_storeDir . '/' . $parts[0] . '/';

            for ($i = 0; $i < $this->_dirLevel; $i++) {
                $file .= substr($md5, $i + $i, 2) . '/';
            }
            $file .= $md5;
        } else {
            $file = $this->_storeDir . '/' . $key;
        }

        return $this->alias->resolve(str_replace(':', '/', $file . $this->_extension));
    }

    /**
     * @param string $id
     *
     * @return bool
     */
    public function exists($id)
    {
        $storeFile = $this->_getFileName($id);

        return is_file($storeFile);
    }

    /**
     * @param string $id
     *
     * @return false|string
     */
    public function get($id)
    {
        $storeFile = $this->_getFileName($id);

        if (is_file($storeFile)) {
            return file_get_contents($storeFile);
        } else {
            return false;
        }
    }

    /**
     * @param array $ids
     *
     * @return array
     */
    public function mGet($ids)
    {
        $idValues = [];

        foreach ($ids as $id) {
            $idValues[$id] = $this->get($id);
        }

        return $idValues;
    }

    /**
     * @param string $id
     * @param string $value
     *
     * @return void
     * @throws \ManaPHP\Store\Engine\Exception
     */
    public function set($id, $value)
    {
        $storeFile = $this->_getFileName($id);

        $storeDir = dirname($storeFile);
        if (!@mkdir($storeDir, 0755, true) && !is_dir($storeDir)) {
            throw new Exception('Create store directory "' . $storeDir . '" failed: ' . error_get_last()['message']);
        }

        if (file_put_contents($storeFile, $value, LOCK_EX) === false) {
            throw new Exception('Write store file"' . $storeFile . '" failed: ' . error_get_last()['message']);
        }

        clearstatcache(true, $storeFile);
    }

    /**
     * @param array $idValues
     *
     * @return void
     * @throws \ManaPHP\Store\Engine\Exception
     */
    public function mSet($idValues)
    {
        foreach ($idValues as $id => $value) {
            $this->set($id, $value);
        }
    }

    /**
     * @param string $id
     *
     * @return void
     */
    public function delete($id)
    {
        $storeFile = $this->_getFileName($id);

        @unlink($storeFile);
    }
}