<?php
namespace ManaPHP\Cli;

use ManaPHP\Component;
use ManaPHP\Cli\Arguments\Exception as ArgumentsException;

class Arguments extends Component implements ArgumentsInterface
{
    /**
     * @var array
     */
    protected $_arguments;

    /**
     * Arguments constructor.
     *
     * @param array $_arguments
     */
    public function __construct($_arguments = null)
    {
        if ($_arguments === null) {
            if (isset($GLOBALS['argv'][2])) {
                $this->_arguments = array_slice($GLOBALS['argv'], 2);
            } else {
                $this->_arguments = [];
            }
        } else {
            $this->_arguments = $_arguments;
        }
    }

    /**
     * @param string $name
     * @param mixed  $defaultValue
     *
     * @return mixed
     * @throws \ManaPHP\Cli\Arguments\Exception
     */
    public function get($name = null, $defaultValue = null)
    {
        foreach (explode(':', $name) as $p) {
            $is_short = strlen($p) === 1;

            for ($i = 0; $i < count($this->_arguments); $i++) {
                if ($is_short) {
                    if ($this->_arguments[$i] !== '-' . $p) {
                        continue;
                    }
                } else {
                    if ($this->_arguments[$i] !== '--' . $p) {
                        continue;
                    }
                }

                if (isset($this->_arguments[$i + 1])) {
                    if ($this->_arguments[$i + 1] === '-') {
                        throw new ArgumentsException('`:argument` argument value is invalid', ['argument' => $name]);
                    } else {
                        return $this->_arguments[$i + 1];
                    }
                }
            }
        }

        return $defaultValue;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        foreach (explode(':', $name) as $p) {
            $is_short = strlen($p) === 1;

            for ($i = 0; $i < count($this->_arguments); $i++) {
                if ($is_short) {
                    if ($this->_arguments[$i] !== '-' . $p) {
                        continue;
                    }
                } else {
                    if ($this->_arguments[$i] !== '--' . $p) {
                        continue;
                    }
                }

                return true;
            }
        }

        return false;
    }
}