<?php

namespace ManaPHP\Cli;

use ManaPHP\Cli\Request\Exception as RequestException;
use ManaPHP\Component;
use ManaPHP\Helper\Reflection;

class Request extends Component implements RequestInterface
{
    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var array
     */
    protected $values = [];

    /**
     * @var string
     */
    protected $prefix;

    /**
     * @var int
     */
    protected $count = 0;

    /**
     * @var string
     */
    protected $request_id;

    public function __construct()
    {
        $this->prefix = bin2hex(random_bytes(4));
    }

    /**
     * @param array $arguments
     *
     * @return static
     * @throws \ManaPHP\Cli\Request\Exception
     */
    public function parse($arguments = null)
    {
        $this->values = [];
        $this->options = [];

        if (in_array(end($arguments), ['', '-', '--'], true)) {
            array_pop($arguments);
        }

        while ($arguments) {
            $o = array_shift($arguments);
            if ($o[0] !== '-') {
                $this->values[] = $o;
                continue;
            }

            if (str_contains($o, '=')) {
                $parts = explode('=', $o, 2);
                $this->options[ltrim($parts[0], '-')] = $parts[1];
                continue;
            }

            if (preg_match('#^-((\w)|-([\w\-]+))=(.*)$#', $o, $match)) {
                $this->options[$match[2]] = $match[4];
                continue;
            }

            if ($o[1] === '-') {
                if (strlen($o) < 3) {
                    throw new RequestException(['long `:option` option is too short', 'option' => $o]);
                }

                $this->options[substr($o, 2)] = !$arguments || $arguments[0][0] === '-' ? 1 : array_shift($arguments);
            } elseif (strlen($o) > 2) {
                if (!$arguments || $arguments[0][0] === '-') {
                    foreach (str_split(substr($o, 1)) as $c) {
                        $this->options[$c] = 1;
                    }
                } else {
                    $this->options[substr($o, 1)] = array_shift($arguments);
                }
            } else {
                $this->options[substr($o, 1)] = !$arguments || $arguments[0][0] === '-' ? 1 : array_shift($arguments);
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
            return $this->options;
        }

        if (str_contains($name, '-')) {
            throw new RequestException(['please remove `-` characters for `:argument` argument', 'argument' => $name]);
        }

        foreach (preg_split('#[|,:]+#', $name) as $o) {
            if (isset($this->options[$o])) {
                return $this->options[$o];
            } elseif (str_contains($o, '_')) {
                $o = strtr($o, '_', '-');
                if (isset($this->options[$o])) {
                    return $this->options[$o];
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
            if (isset($this->options[$p])) {
                return true;
            } elseif (str_contains($p, '_')) {
                $p = strtr($p, '_', '-');
                if (isset($this->options[$p])) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param int   $position
     * @param mixed $default
     *
     * @return string
     */
    public function getValue($position, $default = null)
    {
        return $this->values[$position] ?? $default;
    }

    public function getValues()
    {
        return $this->values;
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
        return $this->request_id;
    }

    /**
     * @param string $request_id
     *
     * @return void
     */
    public function setRequestId($request_id = null)
    {
        if ($request_id) {
            $this->request_id = $request_id;
        } else {
            $this->request_id = $this->prefix . sprintf('%08x', $this->count++);
        }
    }

    /**
     * @param object $instance
     * @param string $action
     *
     * @return void
     */
    public function completeShortNames($instance, $action)
    {
        $shorts = [];
        foreach (Reflection::reflectMethod($instance, $action)->getParameters() as $parameter) {
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

        foreach ($this->options as $k => $v) {
            /** @noinspection NotOptimalIfConditionsInspection */
            if (is_string($k) && strlen($k) === 1 && isset($shorts[$k])) {
                $verbose = $shorts[$k];
                if (!isset($this->options[$verbose])) {
                    $this->options[$verbose] = $v;
                }
            }
        }
    }
}