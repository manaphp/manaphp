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
    protected $_options = [];

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
        $this->parse($arguments);
    }

    /**
     * @param array|string $arguments
     *
     * @return static
     * @throws \ManaPHP\Cli\Arguments\Exception
     */
    public function parse($arguments = null)
    {
        $this->_options = [];
        $this->_values = [];

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
            $this->_parse($arguments);
        }

        return $this;
    }

    /**
     * @param array $args
     *
     * @return static
     * @throws \ManaPHP\Cli\Arguments\Exception
     */
    public function _parse($args)
    {
        $this->_values = [];
        $this->_options = [];

        while ($args) {
            $o = array_shift($args);
            if ($o[0] !== '-') {
                $this->_values[] = $o;
                continue;
            }

            if (preg_match('#^-((\w)|-([\w-_]+))=(.*)$#', $o, $match)) {
                $this->_options[$match[2]] = $match[4];
                continue;
            }

            if ($o[1] === '-') {
                if (strlen($o) < 3) {
                    throw new ArgumentsException(['long `:option` option is too short', 'option' => $o]);
                }

                $this->_options[substr($o, 2)] = !$args || $args[0] === '-' ? 1 : array_shift($args);
            } elseif (strlen($o) > 2) {
                if (!$args || $args[0][0] === '-') {
                    /** @noinspection PhpParamsInspection */
                    foreach (str_split(substr($o, 1), 1) as $c) {
                        $this->_options[$c] = 1;
                    }
                } else {
                    $this->_options[substr($o, 1)] = array_shift($args);
                }
            } else {
                $this->_options[substr($o, 1)] = !$args || $args[0] === '-' ? 1 : array_shift($args);
            }
        }

        return $this;
    }

    /**
     * @param string|int $name
     * @param mixed      $default
     *
     * @return mixed
     * @throws \ManaPHP\Cli\Arguments\Exception
     */
    public function getOption($name = null, $default = null)
    {
        if ($name === null) {
            return $this->_options;
        }

        if (strpos($name, '-') !== false) {
            throw new ArgumentsException(['please remove `-` characters for `:argument` argument', 'argument' => $name]);
        }

        foreach (preg_split('#[|,:]+#', $name) as $o) {
            if (isset($this->_options[$o])) {
                return $this->_options[$o];
            } elseif (strpos($o, '_') !== false) {
                $o = strtr($o, '_', '-');
                if (isset($this->_options[$o])) {
                    return $this->_options[$o];
                }
            }
        }

        if ($default === null) {
            $options = [];
            foreach (preg_split('#[|,:]+#', $name) as $opt) {
                $options[] = (strlen($opt) === 1 ? '-' : '--') . $opt;
            }

            throw new ArgumentsException('missing required options `' . implode('` or `', $options) . '` option');
        }

        return $default;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasOption($name)
    {
        foreach (preg_split('#[|,:]+#', $name) as $p) {
            if (isset($this->_options[$p])) {
                return true;
            } elseif (strpos($p, '_') !== false) {
                $p = strtr($p, '_', '-');
                if (isset($this->_options[$p])) {
                    return true;
                }
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