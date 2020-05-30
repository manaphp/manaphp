<?php

namespace ManaPHP\Cli;

use ManaPHP\Cli\Request\Exception as RequestException;
use ManaPHP\Component;
use ReflectionMethod;

/**
 * Class ManaPHP\Cli\Request
 *
 * @package ManaPHP\Cli
 */
class Request extends Component implements RequestInterface
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
     * @var string
     */
    protected $_prefix;

    /**
     * @var int
     */
    protected $_count = 0;

    /**
     * @var string
     */
    protected $_request_id;

    public function __construct()
    {
        $this->_prefix = bin2hex(random_bytes(4));
    }

    /**
     * @param array|string $arguments
     *
     * @return static
     * @throws \ManaPHP\Cli\Request\Exception
     */
    public function parse($arguments = null)
    {
        $this->_options = [];
        $this->_values = [];

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
     * @throws \ManaPHP\Cli\Request\Exception
     */
    protected function _parse($args)
    {
        $this->_values = [];
        $this->_options = [];

        if (in_array(end($args), ['', '-', '--'], true)) {
            array_pop($args);
        }

        while ($args) {
            $o = array_shift($args);
            if ($o[0] !== '-') {
                $this->_values[] = $o;
                continue;
            }

            if (preg_match('#^-((\w)|-([\w\-]+))=(.*)$#', $o, $match)) {
                $this->_options[$match[2]] = $match[4];
                continue;
            }

            if ($o[1] === '-') {
                if (strlen($o) < 3) {
                    throw new RequestException(['long `:option` option is too short', 'option' => $o]);
                }

                $this->_options[substr($o, 2)] = !$args || $args[0] === '-' ? 1 : array_shift($args);
            } elseif (strlen($o) > 2) {
                if (!$args || $args[0][0] === '-') {
                    foreach (str_split(substr($o, 1)) as $c) {
                        $this->_options[$c] = 1;
                    }
                } else {
                    $this->_options[substr($o, 1)] = array_shift($args);
                }
            } else {
                $this->_options[substr($o, 1)] = !$args || $args[0][0] === '-' ? 1 : array_shift($args);
            }
        }

        return $this;
    }

    /**
     * @param string|int $name
     * @param mixed      $default
     *
     * @return mixed
     * @throws \ManaPHP\Cli\Request\Exception
     */
    public function get($name = null, $default = null)
    {
        if ($name === null) {
            return $this->_options;
        }

        if (str_contains($name, '-')) {
            throw new RequestException(['please remove `-` characters for `:argument` argument', 'argument' => $name]);
        }

        foreach (preg_split('#[|,:]+#', $name) as $o) {
            if (isset($this->_options[$o])) {
                return $this->_options[$o];
            } elseif (str_contains($o, '_')) {
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

            throw new RequestException('missing required options `' . implode('` or `', $options) . '` option');
        }

        return $default;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        foreach (preg_split('#[|,:]+#', $name) as $p) {
            if (isset($this->_options[$p])) {
                return true;
            } elseif (str_contains($p, '_')) {
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

    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getServer($name = null, $default = '')
    {
        if ($name === null) {
            return $_SERVER;
        } else {
            return $_SERVER[$name] ?? $default;
        }
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasServer($name)
    {
        return isset($_SERVER[$name]);
    }

    /**
     * @return string
     */
    public function getRequestId()
    {
        return $this->_request_id;
    }

    /**
     * @param string $request_id
     *
     * @return void
     */
    public function setRequestId($request_id = null)
    {
        if ($request_id) {
            $this->_request_id = $request_id;
        } else {
            $this->_request_id = $this->_prefix . sprintf('%08x', $this->_count++);
        }
    }

    /**
     * @param object $instance
     * @param string $command
     *
     * @return void
     */
    public function completeShortNames($instance, $command)
    {
        $shorts = [];
        foreach ((new ReflectionMethod($instance, $command))->getParameters() as $parameter) {
            $name = $parameter->getName();

            $type = $parameter->getType();
            if ($type && !$type->isBuiltin()) {
                continue;
            }

            if (str_ends_with($name, 'Service')) {
                continue;
            }

            $short = $name[0];
            if (isset($names[$short])) {
                $shorts[$short] = false;
            } else {
                $shorts[$short] = $name;
            }
        }
        $shorts = array_filter($shorts);

        foreach ($this->_options as $k => $v) {
            /** @noinspection NotOptimalIfConditionsInspection */
            if (is_string($k) && strlen($k) === 1 && isset($shorts[$k])) {
                $verbose = $shorts[$k];
                if (!isset($this->_options[$verbose])) {
                    $this->_options[$verbose] = $v;
                }
            }
        }
    }
}