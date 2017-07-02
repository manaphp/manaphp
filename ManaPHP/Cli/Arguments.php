<?php

namespace ManaPHP\Cli;

use ManaPHP\Cli\Arguments\Exception as ArgumentsException;
use ManaPHP\Component;

/**
 * Class ManaPHP\Cli\Arguments
 *
 * @package ManaPHP\Cli
 */
class Arguments extends Component implements ArgumentsInterface
{
    /**
     * @var array
     */
    protected $_arguments;

    /**
     * Arguments constructor.
     *
     * @param array $arguments
     */
    public function __construct($arguments = null)
    {
        if ($arguments === null) {
            if (count($GLOBALS['argv']) > 3) {
                $this->_arguments = array_slice($GLOBALS['argv'], 3);
            } else {
                $this->_arguments = [];
            }
        } else {
            $this->_arguments = $arguments;
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
        if ($name === null) {
            return $this->_arguments;
        }

        if (strpos($name, '-') !== false) {
            throw new ArgumentsException('please remove `-` characters for `:argument` argument', ['argument' => $name]);
        }

        foreach (explode(':', $name) as $p) {
            $is_short = strlen($p) === 1;

            foreach ($this->_arguments as $i => $argument) {
                if ($is_short) {
                    if ($argument !== '-' . $p) {
                        continue;
                    }
                } else {
                    if ($argument !== '--' . $p) {
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
            foreach ($this->_arguments as $argument) {
                if (strlen($p) === 1) {
                    if ($argument === '-' . $p) {
                        return true;
                    }
                } else {
                    if ($argument === '--' . $p) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}