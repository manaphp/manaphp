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
    protected $_options;

    /**
     * @var array
     */
    protected $_values = [];

    /**
     * Arguments constructor.
     *
     * @param array $arguments
     *
     * @throws \ManaPHP\Cli\Arguments\Exception
     */
    public function __construct($arguments = null)
    {
        if ($arguments === null) {
            if (isset($GLOBALS['argv'][1]) && $GLOBALS['argv'][1][0] === '/') {
                $arguments = [$GLOBALS['argv'][1]];
            } else {
                /** @noinspection CallableParameterUseCaseInTypeContextInspection */
                $arguments = array_slice($GLOBALS['argv'], 3);
            }
        }

        if (count($arguments) === 1 && $arguments[0][0] === '/') {
            $query = parse_url($arguments[0], PHP_URL_QUERY);
            parse_str($query, $this->_options);
            if (($fragment = parse_url($arguments[0], PHP_URL_FRAGMENT)) !== null) {
                $this->_values[] = $fragment;
            }
        } else {
            $this->_options = $this->_parse($arguments);
        }
    }

    /**
     * @param array $args
     *
     * @return array
     * @throws \ManaPHP\Cli\Arguments\Exception
     */
    public function _parse($args)
    {
        $r = [];
        while (count($args) !== 0) {
            $o = array_shift($args);
            if (strlen($o) < 2 || $o[0] !== '-') {
                $this->_values[] = $o;
                continue;
            }

            if ($o[1] === '-') {
                if (strlen($o) < 3) {
                    throw new ArgumentsException(['long `:option` option is too short', 'option' => $o]);
                }
                if (count($args) >= 1 && $args[0] !== '-') {
                    $r[substr($o, 2)] = array_shift($args);
                } else {
                    $r[substr($o, 2)] = 1;
                }
            } else {
                if (strlen($o) > 2) {
                    /** @noinspection PhpParamsInspection */
                    foreach (array_chunk(substr($o, 1), 1) as $c) {
                        $r[$c] = 1;
                    }
                } else {
                    if (count($args) >= 1 && $args[0] !== '-') {
                        $r[substr($o, 1)] = array_shift($args);
                    } else {
                        $r[substr($o, 1)] = 1;
                    }
                }
            }
        }

        return $r;
    }

    /**
     * @param string|int $name
     * @param mixed      $defaultValue
     *
     * @return mixed
     * @throws \ManaPHP\Cli\Arguments\Exception
     */
    public function getOption($name = null, $defaultValue = null)
    {
        if ($name === null) {
            return $this->_options;
        }

        if (strpos($name, '-') !== false) {
            throw new ArgumentsException(['please remove `-` characters for `:argument` argument', 'argument' => $name]);
        }

        foreach (explode(strpos($name, '|') !== false ? '|' : ':', $name) as $o) {
            if (isset($this->_options[$o])) {
                return $this->_options[$o];
            }
        }

        if ($defaultValue === null) {
            $options = [];
            foreach (explode(strpos($name, '|') !== false ? '|' : ':', $name) as $opt) {
                if (strlen($opt) === 1) {
                    $options[] = '-' . $opt;
                } else {
                    $options[] = '--' . $opt;
                }
            }

            throw new ArgumentsException('missing required options `' . implode('` or `', $options) . '` option');
        }

        return $defaultValue;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasOption($name)
    {
        foreach (explode(strpos($name, '|') !== false ? '|' : ':', $name) as $p) {
            if (isset($this->_options[$p])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param int $position
     *
     * @return string
     */
    public function getValue($position)
    {
        return isset($this->_values[$position]) ? $this->_values[0] : null;
    }

    public function getValues()
    {
        return $this->_values;
    }
}