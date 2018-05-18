<?php
namespace ManaPHP;

use ManaPHP\Exception\FileNotFoundException;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\RuntimeException;

class Dotenv extends Component implements DotenvInterface
{
    /**
     * @var string
     */
    protected $_file = '@root/.env';

    /**
     * @var array
     */
    protected $_env = [];

    /**
     * @var bool
     */
    protected $_toEnv;

    /**
     * @var bool
     */
    protected $_toServer;

    /**
     * DotEnv constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['toEnv'])) {
            $this->_toEnv = $options['toEnv'];
        }

        if (isset($options['toServer'])) {
            $this->_toServer = $options['toServer'];
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
    public function load($file = null)
    {
        $file = $this->alias->resolve($file ?: $this->_file);
        $parsed_file = $file . '.php';
        if (is_file($parsed_file)) {
            /** @noinspection PhpIncludeInspection */
            $env = require $parsed_file;
        } elseif (is_file($file)) {
            $lines = file($file, FILE_SKIP_EMPTY_LINES | FILE_IGNORE_NEW_LINES);
            if ($lines === false) {
                throw new RuntimeException(['read `:file` failed', 'file' => $file]);
            }
            $env = $this->parse($lines);
        } else {
            throw new FileNotFoundException(['.env file is not found: :file', 'file' => $file]);
        }

        if ($this->_toEnv) {
            /** @noinspection AdditionOperationOnArraysInspection */
            $_ENV += $env;
        }

        if ($this->_toServer) {
            /** @noinspection AdditionOperationOnArraysInspection */
            $_SERVER += $env;
        }

        return $this;
    }

    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed|array
     */
    public function getEnv($name, $default = null)
    {
        if ($name === null) {
            return $this->_env;
        } else {
            return isset($this->_env[$name]) ? $this->_env[$name] : null;
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
            $name = $parts[0];
            $value = $parts[1];

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
                    preg_match_all('#\$\{([\w\.]+)\}#', $value, $matches, PREG_PATTERN_ORDER);
                    foreach ($matches[1] as $match) {
                        $ref_name = $match;
                        if (!isset($data[$ref_name])) {
                            throw new InvalidValueException('`:ref` ref variable is not exists: :value', ['ref' => $ref_name, 'value' => $value]);
                        }
                        $value = strtr($value, ['${' . $ref_name . '}' => $data[$ref_name]]);
                    }
                } elseif (strpos($value, '$') !== false) {
                    preg_match_all('#\$([A-Z_\d]+)#', $value, $matches, PREG_PATTERN_ORDER);
                    foreach ($matches[1] as $match) {
                        $ref_name = $match;
                        if (!isset($data[$ref_name])) {
                            throw new InvalidValueException('`:ref` ref variable is not exists: :value', ['ref' => $ref_name, 'value' => $value]);
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
