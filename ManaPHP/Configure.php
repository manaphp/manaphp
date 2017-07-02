<?php

namespace ManaPHP;

use ManaPHP\Configure\Exception as ConfigureException;

/**
 * Class ManaPHP\Configure
 *
 * @package configure
 *
 */
class Configure extends Component implements ConfigureInterface, \ArrayAccess
{
    /**
     * @var bool
     */
    public $debug = true;

    /**
     * @var string
     */
    public $appID = 'manaphp';

    /**
     * @var \ManaPHP\Configure\EngineInterface[]
     */
    protected $_resolved = [];

    /**
     * @var array
     */
    protected $_engines = [];

    /**
     * @var array
     */
    protected $_files = [];

    /**
     * @var
     */
    protected $_data = [];

    /**
     * Configure constructor.
     *
     * @param array $engines
     */
    public function __construct(
        $engines = [
            '.ini' => 'ManaPHP\Configure\Engine\Ini',
            '.php' => 'ManaPHP\Configure\Engine\Php',
            '.json' => 'ManaPHP\Configure\Engine\Json'
        ]
    )
    {
        $this->_engines = $engines;
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        $data = [];

        foreach (get_object_vars($this) as $k => $v) {
            if ($k === '_resolved') {
                continue;
            }
            if (is_scalar($v) || is_array($v) || $v instanceof \stdClass) {
                $data[$k] = $v;
            }
        }
        return $data;
    }

    /**
     * @param string $file
     * @param string $mode
     *
     * @return array
     * @throws \ManaPHP\Configure\Exception
     */
    protected function _load($file, $mode)
    {
        $this->_files[$file] = true;

        $ext = '.' . pathinfo($file, PATHINFO_EXTENSION);
        if (!isset($this->_engines[$ext])) {
            throw new ConfigureException('`:ext` file type engine is not registered for load `:file` file', ['ext' => $ext, 'file' => $file]);
        }

        if (!isset($this->_resolved[$ext])) {
            $this->_resolved[$ext] = $this->_dependencyInjector->getShared($this->_engines[$ext]);
        }

        $data = $this->_resolved[$ext]->load($file);

        if ($mode !== null) {
            foreach ($data as $k => $v) {
                if (strpos($k, ':') !== false) {
                    list($kName, $kMode) = explode(':', $k);

                    if ($kMode === $mode) {
                        if (isset($data[$kName])) {
                            /** @noinspection SlowArrayOperationsInLoopInspection */
                            $data[$kName] = array_merge($data[$kName], $v);
                        } else {
                            $data[$kName] = $v;
                        }
                    }

                    unset($data[$k]);
                }
            }
        }

        return $data;
    }

    /**
     * @return static
     */
    public function reset()
    {
        $this->_files = [];
        $this->_data = [];

        return $this;
    }

    /**
     * @param string $file
     * @param string $mode
     *
     * @return static
     * @throws \ManaPHP\Configure\Exception
     */
    public function load($file, $mode = null)
    {
        if (strpos($file, '*') !== false) {
            foreach ($this->_dependencyInjector->filesystem->glob($file) as $f) {
                if (is_file($f)) {
                    /** @noinspection SlowArrayOperationsInLoopInspection */
                    $this->_data = array_merge($this->_data, $this->_load($f, $mode));
                }
            }
        } else {
            $this->_data = array_merge($this->_load($this->_dependencyInjector->alias->resolve($file), $mode));
        }

        return $this;
    }

    /**
     * @param string $offset
     *
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        if ($offset === '') {
            return $this->_data;
        } else {
            return isset($this->_data[$offset]) ? $this->_data[$offset] : null;
        }
    }

    /**
     * @param string $offset
     * @param mixed  $value
     */
    public function offsetSet($offset, $value)
    {
        $this->_data[$offset] = $value;
    }

    /**
     * @param string $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->_data[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->_data[$offset]);
    }

    /**
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
    {
        $this->{$name} = $value;
    }
}