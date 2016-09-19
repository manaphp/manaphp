<?php
namespace ManaPHP\Cli;

use ManaPHP\Component;

class Arguments extends Component implements ArgumentsInterface
{
    /**
     * @var array
     */
    protected $_options = [];

    /**
     * @var array
     */
    protected $_arguments;

    /**
     * @param string $name
     * @param int    $type
     * @param string $description
     *
     * @return void
     */
    public function set($name, $type, $description = '')
    {
        $this->_options[$name] = ['type' => $type, 'description' => $description];
    }

    /**
     * @param string $name
     * @param mixed  $defaultValue
     *
     * @return mixed
     */
    public function get($name = null, $defaultValue = null)
    {
        if ($name === null) {
            if (isset($GLOBALS['argv'][2])) {
                return array_slice($GLOBALS['argv'], 2);
            } else {
                return [];
            }
        }

        if ($this->_arguments === null) {
            $this->_arguments = [];
        }

        return isset($this->_arguments[$name]) ? $this->_arguments[$name] : $defaultValue;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        if ($this->_arguments === null) {
            $this->_arguments = [];
        }

        return isset($this->_arguments[$name]);
    }

    public function _parse()
    {
        $shorts = '';

        foreach ($this->_options as $k => $v) {

        }
    }
}