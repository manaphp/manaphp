<?php

namespace ManaPHP\Configuration;

use ManaPHP\Component;
use ManaPHP\Data\Redis\Connection;
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
     * @var int
     */
    protected $_ttl = 1;

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

        if (isset($options['ttl'])) {
            $this->_ttl = (int)$options['ttl'];
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
        if ($file === null) {
            if ($v = $_SERVER['DOTENV_URL'] ?? null) {
                $file = $v;
            } elseif ($v = getenv('DOTENV_URL')) {
                $file = (string)$v;
            } else {
                $file = '@root/.env';
            }
        }

        $this->_file = $file;

        if (str_contains($file, '://')) {
            if (!is_file($config_file = $this->alias->resolve('@config/app.php'))) {
                throw new RuntimeException('@config/app.php file is not exists');
            }

            if (!preg_match('#[\'"]id[\'"]\s*=>\s*[\'"]([\w-]+)#', file_get_contents($config_file), $match)) {
                throw new RuntimeException('missing app_id config');
            }
            $app_id = $match[1];

            $key = ".env:$app_id";
            if (!function_exists('apcu_fetch') || !$lines = apcu_fetch($key)) {
                $scheme = parse_url($file, PHP_URL_SCHEME);
                if ($scheme === 'redis') {
                    $redis = (new Connection($file))->getConnect();
                    $lines = $redis->hGet('.env', 'default') . PHP_EOL . $redis->hGet('.env', $app_id);
                } else {
                    throw new RuntimeException(['`:scheme` scheme is not support', 'scheme' => $scheme]);
                }

                if (function_exists('apcu_store')) {
                    apcu_store($key, $lines, $this->_ttl);
                }
            }
        } else {
            if (!is_file($file = $this->alias->resolve($file))) {
                throw new FileNotFoundException(['.env file is not found: :file', 'file' => $file]);
            }

            $lines = file_get_contents($file);
        }

        if (!$lines) {
            throw new RuntimeException('.env content is empty');
        }

        $lines = preg_split('#[\r\n]+#', $lines, -1, PREG_SPLIT_NO_EMPTY);

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
    public function get($key = null, $default = null)
    {
        if ($key === null) {
            return $this->_env;
        }

        if (isset($this->_env[$key])) {
            $value = $this->_env[$key];
        } elseif (!$this->_file) {
            throw new PreconditionException('@root/.env file is not exists');
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
                    throw new InvalidValueException(['the value of `%s` key is not valid json format array', $key]);
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
                throw new InvalidArgumentException(['`%s` key value is not a valid bool value: %s', $key, $value]);
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
            if (str_starts_with($line, 'export ')) {
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

                if (str_contains($value, '${')) {
                    preg_match_all('#\${([\w.]+)}#', $value, $matches);
                    foreach ((array)$matches[1] as $match) {
                        $ref_name = $match;
                        if (!isset($data[$ref_name])) {
                            throw new InvalidValueException(['`%s` ref variable is not exists: %s', $ref_name, $value]);
                        }
                        $value = strtr($value, ['${' . $ref_name . '}' => $data[$ref_name]]);
                    }
                } elseif (str_contains($value, '$')) {
                    preg_match_all('#\$([A-Z_\d]+)#', $value, $matches);
                    foreach ((array)$matches[1] as $match) {
                        $ref_name = $match;
                        if (!isset($data[$ref_name])) {
                            throw new InvalidValueException(['`%s` ref variable is not exists: %s', $ref_name, $value]);
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
