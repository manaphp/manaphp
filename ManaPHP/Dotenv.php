<?php
namespace ManaPHP;

use ManaPHP\Exception\FileNotFoundException;
use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\PreconditionException;
use ManaPHP\Exception\RuntimeException;

class Dotenv extends Component implements DotenvInterface
{
    /**
     * @var bool
     */
    protected $_to_env;

    /**
     * @var string
     */
    protected $_file;

    /**
     * @var array
     */
    protected $_env = [];

    /**
     * DotEnv constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['to_env'])) {
            $this->_to_env = $options['to_env'];
        }

        foreach ($_ENV as $k => $v) {
            if ($v === 'true') {
                $_ENV[$k] = true;
            } elseif ($v === 'false') {
                $_ENV[$k] = false;
            } elseif ($v === 'null') {
                $_ENV[$k] = null;
            }
        }

        $this->_env = $_ENV;
    }

    /**
     * @param string $file
     *
     * @return static
     */
    public function load($file = '@root/.env')
    {
        $this->_file = $file;

        if (!is_file($file = $this->alias->resolve($file))) {
            throw new FileNotFoundException(['.env file is not found: :file', 'file' => $file]);
        }

        if (($lines = file($file, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES)) === false) {
            throw new RuntimeException(['read `:file` failed', 'file' => $file]);
        }
        $env = $this->parse($lines);

        /** @noinspection AdditionOperationOnArraysInspection */
        $this->_env += $env;

        if ($this->_to_env) {
            /** @noinspection AdditionOperationOnArraysInspection */
            $_ENV += $env;
        }

        return $this;
    }

    /**
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed|array
     */
    public function get($key, $default = null)
    {
        if ($key === null) {
            return $this->_env;
        }

        if (isset($this->_env[$key])) {
            $value = $this->_env[$key];
        } elseif (!$this->_file) {
            throw new PreconditionException('@root/.env file is not exists: you can copy from @root/.env.sample to @root/.env file');
        } elseif ($default !== null) {
            $value = $default;
        } else {
            throw new InvalidArgumentException(['`:key` key value is not exists in .env file', 'key' => $key]);
        }

        if (is_array($default)) {
            if (is_array($value)) {
                return $value;
            } elseif ($value !== '' && $value[0] === '{') {
                if (is_array($r = json_parse($value))) {
                    return $r;
                } else {
                    throw new InvalidValueException(['the value of `:key` key is not valid json format array', 'key' => $key]);
                }
            } else {
                return preg_split('#[\s,]+#', $value, -1, PREG_SPLIT_NO_EMPTY);
            }
        } elseif (is_int($default)) {
            return (int)$value;
        } elseif (is_float($default)) {
            return (float)$value;
        } elseif (is_bool($default)) {
            if (is_bool($value)) {
                return $value;
            } elseif (in_array(strtolower($value), ['1', 'on', 'true'], true)) {
                return true;
            } elseif (in_array(strtolower($value), ['0', 'off', 'false'], true)) {
                return false;
            } else {
                throw new InvalidArgumentException(['`:key` key value is not a valid bool value: :value', 'key' => $key, 'value' => $value]);
            }
        } else {
            return $value;
        }
    }

    /**
     * @param array $lines
     *
     * @return array
     */
    public function parse($lines)
    {
        $data = [];
        foreach ($lines as $line) {
            if ($line === '' || $line[0] === '#') {
                continue;
            }

            if ($pos = strpos($line, ' # ')) {
                $line = substr($line, 0, $pos);
            }
            $line = trim($line);
            if (strpos($line, 'export ') === 0) {
                $parts = explode('=', ltrim(substr($line, 7)), 2);
            } else {
                $parts = explode('=', $line, 2);
            }

            if (count($parts) !== 2) {
                throw new InvalidValueException(['invalid line: :line, has no = character', 'line' => $line]);
            }
            list($name, $value) = $parts;

            if ($value === '') {
                null;
            } elseif ($value === 'true') {
                $value = true;
            } elseif ($value === 'false') {
                $value = false;
            } elseif ($value === 'null') {
                $value = null;
            } else {
                $fc = $value[0];
                if (($fc === '"' || $fc === "'") && $value[strlen($value) - 1] === $fc) {
                    $value = substr($value, 1, -1);
                    $value = strtr($value, ["\\$fc" => $fc]);
                }
                $value = strtr($value, ['\n' => PHP_EOL]);

                if (strpos($value, '${') !== false) {
                    preg_match_all('#\${([\w.]+)}#', $value, $matches);
                    foreach ((array)$matches[1] as $match) {
                        $ref_name = $match;
                        if (!isset($data[$ref_name])) {
                            throw new InvalidValueException('`:ref` ref variable is not exists: :value', ['ref' => $ref_name, 'value' => $value]);
                        }
                        $value = strtr($value, ['${' . $ref_name . '}' => $data[$ref_name]]);
                    }
                } elseif (strpos($value, '$') !== false) {
                    preg_match_all('#\$([A-Z_\d]+)#', $value, $matches);
                    foreach ((array)$matches[1] as $match) {
                        $ref_name = $match;
                        if (!isset($data[$ref_name])) {
                            throw new InvalidValueException(['`:ref` ref variable is not exists: :value', 'ref' => $ref_name, 'value' => $value]);
                        }
                        $value = strtr($value, ['$' . $ref_name => $data[$ref_name]]);
                    }
                }
            }

            $data[$name] = $value;
        }

        return $data;
    }
}
