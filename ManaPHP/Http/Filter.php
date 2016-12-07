<?php
namespace ManaPHP\Http;

use ManaPHP\Component;
use ManaPHP\Http\Filter\Exception as FilterException;
use ManaPHP\Utility\Text;

/**
 * Class ManaPHP\Http\Filter
 *
 * @package filter
 *
 * @property \ManaPHP\Security\SecintInterface $secint
 */
class Filter extends Component implements FilterInterface
{
    /**
     * @var array
     */
    protected $_filters = [];

    /**
     * @var array|string
     */
    protected $_messages = 'en';

    /**
     * @var array
     */
    protected $_attributes = [];

    /**
     * @var string
     */
    protected $_defaultMessage = 'The :attribute format is invalid.';

    /**
     * @var bool
     */
    protected $_xssByReplace = true;

    /**
     * @var array
     */
    protected $_rules = [];

    /**
     * Filter constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        foreach (get_class_methods($this) as $method) {
            if (Text::startsWith($method, '_filter_')) {
                $this->_filters[substr($method, 8)] = [$this, $method];
            }
        }

        if (is_object($options)) {
            $options = (array)$options;
        }

        if (isset($options['messages'])) {
            $this->_messages = $options['messages'];
        }

        if (isset($options['defaultMessage'])) {
            $this->_defaultMessage = $options['defaultMessage'];
        }

        if (isset($options['xssByReplace'])) {
            $this->_xssByReplace = $options['xssByReplace'];
        }
    }

    /**
     * @param string   $name
     * @param callable $method
     *
     * @return static
     */
    public function addFilter($name, $method)
    {
        $this->_filters[$name] = $method;

        return $this;
    }

    /**
     * @param string $attribute
     * @param string $rule
     * @param string $name
     *
     * @return static
     */
    public function addRule($attribute, $rule, $name = null)
    {
        $this->_rules[$attribute] = $rule;

        if ($name !== null) {
            $this->_attributes[$attribute] = $name;
        }

        return $this;
    }

    /**
     * @param string $rule
     *
     * @return array
     */
    protected function _parseRule($rule)
    {
        $parts = (array)explode('|', $rule);

        $filters = [];
        foreach ($parts as $part) {
            if (Text::contains($part, ':')) {
                $parts2 = explode(':', $part, 2);
                $filter = $parts2[0];
                $parameters = explode(',', $parts2[1]);
            } else {
                $filter = $part;
                $parameters = [];
            }

            $filter = trim($filter);
            if ($filter === '') {
                continue;
            }

            $filters[$filter] = $parameters;
        }

        return $filters;
    }

    /**
     * @param string                $attribute
     * @param string                $rule
     * @param string|int|bool|array $value
     *
     * @return mixed
     * @throws \ManaPHP\Http\Filter\Exception
     */
    public function sanitize($attribute, $rule, $value)
    {
        if ($rule === null && isset($this->_rules[$attribute])) {
            $rule = $this->_rules[$attribute];
        }

        if ($rule === null && $value === null) {
            return null;
        }

        $filters = $this->_parseRule($rule);

        if ($value === null && !isset($filters['default'])) {
            $this->_sanitize($attribute, 'required', [], null);
        }

        if (is_int($value)) {
            $value = (string)$value;
        } elseif (is_bool($value)) {
            $value = $value ? '1' : '0';
        } elseif ($value === null) {
            $value = '';
        }

        foreach ($filters as $filter => $parameters) {
            $value = $this->_sanitize($attribute, $filter, $parameters, $value);
        }

        if (is_string($value) && !isset($filters['ignore']) && !isset($filters['xss'])) {
            $parameters = [];
            $value = $this->_sanitize($attribute, 'xss', $parameters, $value);
        }

        return $value;
    }

    /**
     * @param string $attribute
     * @param string $filter
     * @param array  $parameters
     * @param mixed  $value
     *
     * @return mixed
     * @throws \ManaPHP\Http\Filter\Exception
     */
    protected function _sanitize($attribute, $filter, $parameters, $value)
    {
        $srcValue = $value;

        if (isset($this->_filters[$filter])) {
            $value = call_user_func_array($this->_filters[$filter], [$value, $parameters]);
        } elseif (function_exists($filter)) {
            $value = call_user_func_array($filter, array_merge([$value], $parameters));
        } else {
            throw new FilterException('`:name` filter is not be recognized'/**m09d0e9938a3a49e27*/, ['name' => $filter]);
        }

        if ($value === null) {
            if (is_string($this->_messages)) {
                if (!Text::contains($this->_messages, '.')) {
                    $file = '@manaphp/Http/Filter/Messages/' . $this->_messages . '.php';
                } else {
                    $file = $this->_messages;
                }

                if (!$this->filesystem->fileExists($file)) {
                    throw new FilterException('`:file` filter message template file is not exists'/**m08523be1bf26d3984*/, ['file' => $file]);
                }

                /** @noinspection PhpIncludeInspection */
                $this->_messages = require $this->alias->resolve($file);
            }

            if (isset($this->_messages[$filter])) {
                $message = $this->_messages[$filter];
            } else {
                $message = $this->_defaultMessage;
            }

            $bind = [];

            $bind['filter'] = $filter;
            $bind['attribute'] = isset($this->_attributes[$attribute]) ? $this->_attributes[$attribute] : $attribute;
            $bind['value'] = $srcValue;
            foreach ($parameters as $k => $parameter) {
                $bind['parameter[' . $k . ']'] = $parameter;
            }

            throw new FilterException($message, $bind);
        }

        return $value;
    }

    /**
     * @param string $value
     *
     * @return string|null
     */
    protected function _filter_required($value)
    {
        if ($value === '') {
            return null;
        } else {
            return $value;
        }
    }

    /**
     * @param mixed $value
     * @param array $parameters
     *
     * @return mixed
     */
    protected function _filter_default($value, $parameters)
    {
        if ($value === '' || $value === null) {
            return $parameters[0];
        } else {
            return $value;
        }
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    protected function _filter_ignore($value)
    {
        return $value;
    }

    /**
     * @param string $value
     * @param array  $parameters
     *
     * @return string
     */
    protected function _filter_xss($value, $parameters)
    {
        if ($value === '') {
            return $value;
        }

        if (count($parameters) === 0) {
            $xssReplace = $this->_xssByReplace;
        } else {
            $xssReplace = $parameters[0];
        }

        if ($xssReplace) {
            $tr = ['<' => '＜', '>' => '＞', '\'' => '‘', '"' => '“', '&' => '＆', '\\' => '＼', '#' => '＃'];
            $value = strtr($value, $tr);
        } else {
            $value = str_replace('<>\'"&\\#', ' ', $value);
        }

        $from = ['\u', '\U'];
        $to = ' ';
        $value = str_replace($from, $to, $value);//http://zone.wooyun.org/content/1253

        return $value;
    }

    /**
     * @param string $value
     *
     * @return bool|null
     */
    protected function _filter_bool($value)
    {
        $trueValues = ['1', 'true'];
        $falseValues = ['0', 'false'];

        if (in_array($value, $trueValues, true)) {
            return true;
        } elseif (in_array($value, $falseValues, true)) {
            return false;
        } else {
            return null;
        }
    }

    /**
     * @param string $value
     *
     * @return int|null
     */
    protected function _filter_int($value)
    {
        if (preg_match('#^[+-]?\d+$#', $value) === 1) {
            return (int)$value;
        } else {
            return null;
        }
    }

    /**
     * @param string $value
     *
     * @return float|null
     */
    protected function _filter_float($value)
    {
        if (filter_var($value, FILTER_VALIDATE_FLOAT) !== false
            && preg_match('#^[+-]?[\d\.]+$#', $value) === 1
        ) {
            return (float)$value;
        } else {
            return null;
        }
    }

    /**
     * @param string $value
     * @param array  $parameters
     *
     * @return int|null
     */
    protected function _filter_date($value, $parameters)
    {
        $timestamp = is_numeric($value) ? $value : strtotime($value);
        if ($timestamp !== false) {
            return date(isset($parameters[0]) ? $parameters[0] : 'Y-m-d H:i:s', $timestamp);
        } else {
            return null;
        }
    }

    /**
     * @param string $value
     * @param array  $parameters
     *
     * @return int|null
     */
    protected function _filter_range($value, $parameters)
    {
        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $value = $this->_filter_float($value);
        if ($value === null || $value < $parameters[0] || $value > $parameters[1]) {
            return null;
        } else {
            return $value;
        }
    }

    /**
     * @param string $value
     * @param array  $parameters
     *
     * @return float|null
     */
    protected function _filter_min($value, $parameters)
    {
        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $value = $this->_filter_float($value);
        if ($value === null || $value < $parameters[0]) {
            return null;
        } else {
            return $value;
        }
    }

    /**
     * @param string $value
     * @param array  $parameters
     *
     * @return float|null
     */
    protected function _filter_max($value, $parameters)
    {
        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $value = $this->_filter_float($value);
        if ($value === null || $value > $parameters[0]) {
            return null;
        } else {
            return $value;
        }
    }

    /**
     * @param string $value
     * @param array  $parameters
     *
     * @return string|null
     */
    protected function _filter_minLength($value, $parameters)
    {
        if (function_exists('mb_strlen')) {
            $length = mb_strlen($value);
        } else {
            $length = strlen($value);
        }

        if ($length >= $parameters[0]) {
            return $value;
        } else {
            return null;
        }
    }

    /**
     * @param string $value
     * @param array  $parameters
     *
     * @return string|null
     */
    protected function _filter_maxLength($value, $parameters)
    {
        if (function_exists('mb_strlen')) {
            $length = mb_strlen($value);
        } else {
            $length = strlen($value);
        }

        if ($length <= $parameters[0]) {
            return $value;
        } else {
            return null;
        }
    }

    /**
     * @param string $value
     * @param array  $parameters
     *
     * @return string|null
     */
    protected function _filter_length($value, $parameters)
    {
        if (function_exists('mb_strlen')) {
            $length = mb_strlen($value);
        } else {
            $length = strlen($value);
        }

        if ($length >= $parameters[0] && $length <= $parameters[1]) {
            return $value;
        } else {
            return null;
        }
    }

    /**
     * @param string $value
     * @param array  $parameters
     *
     * @return string|null
     */
    protected function _filter_equal($value, $parameters)
    {
        if ($value === $parameters[0]) {
            return $value;
        } else {
            return null;
        }
    }

    /**
     * @param string $value
     * @param array  $parameters
     *
     * @return string|null
     */
    protected function _filter_regex($value, $parameters)
    {
        if (preg_match($parameters[0], $value) === 1) {
            return $value;
        } else {
            return null;
        }
    }

    /**
     * @param string $value
     *
     * @return string|null
     */
    protected function _filter_alpha($value)
    {
        /** @noinspection NotOptimalRegularExpressionsInspection */
        if (preg_match('#^[a-zA-Z]+$#', $value) === 1) {
            return $value;
        } else {
            return null;
        }
    }

    /**
     * @param string $value
     *
     * @return string|null
     */
    protected function _filter_digit($value)
    {
        /** @noinspection NotOptimalRegularExpressionsInspection */
        if (preg_match('#^\d+$#', $value) === 1) {
            return $value;
        } else {
            return null;
        }
    }

    /**
     * @param string $value
     *
     * @return string|null
     */
    protected function _filter_alnum($value)
    {
        /** @noinspection NotOptimalRegularExpressionsInspection */
        if (preg_match('#^[a-zA-Z0-9]+$#', $value) === 1) {
            return $value;
        } else {
            return null;
        }
    }

    /**
     * @param string $value
     *
     * @return string
     */
    protected function _filter_lower($value)
    {
        return strtolower($value);
    }

    /**
     * @param string $value
     *
     * @return string
     */
    protected function _filter_upper($value)
    {
        return strtoupper($value);
    }

    /**
     * @param string $value
     *
     * @return string|null
     */
    protected function _filter_account($value)
    {
        if (preg_match('#^[a-z][a-z_\d]{1,14}[a-z\d]$#', $value) === 1) {
            return $value;
        } else {
            return null;
        }
    }

    /**
     * @param string $value
     *
     * @return string|null
     */
    protected function _filter_password($value)
    {
        if ($value !== '') {
            return $value;
        } else {
            return null;
        }
    }

    /**
     * @param string $value
     *
     * @return string|null
     */
    protected function _filter_email($value)
    {
        $value = trim($value);
        if (filter_var($value, FILTER_VALIDATE_EMAIL) !== false) {
            return $value;
        } else {
            return null;
        }
    }

    /**
     * @param string $value
     *
     * @return string|null
     */
    protected function _filter_url($value)
    {
        $value = trim($value);

        if (filter_var($value, FILTER_VALIDATE_URL) !== false) {
            $parts = explode('://', $value, 2);
            $scheme = strtolower($parts[0]);
            $path = $parts[1];
            if ($scheme !== 'http' && $scheme !== 'https') {
                return null;
            } else {
                return $scheme . '://' . $path;
            }
        } else {
            return null;
        }
    }

    /**
     * @param string $value
     * @param array  $parameters
     *
     * @return string|null
     */
    protected function _filter_in($value, $parameters)
    {
        if (in_array($value, $parameters, true)) {
            return $value;
        } else {
            return null;
        }
    }

    /**
     * @param string $value
     * @param array  $parameters
     *
     * @return string|null
     */
    protected function _filter_not_in($value, $parameters)
    {
        if (!in_array($value, $parameters, true)) {
            return $value;
        } else {
            return null;
        }
    }

    /**
     * @param string $value
     *
     * @return mixed|null
     */
    protected function _filter_json($value)
    {
        if (is_scalar($value)) {
            $a = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $a;
            }
        }

        return null;
    }

    /**
     * @param string $value
     *
     * @return string|null
     */
    protected function _filter_mobile($value)
    {
        $value = trim($value);

        if (preg_match('#^1[3-8]\d{9}$#', $value) === 1) {
            return $value;
        } else {
            return null;
        }
    }

    /**
     * @param string $value
     *
     * @return string|null
     */
    protected function _filter_ip($value)
    {
        $value = trim($value);

        if (filter_var($value, FILTER_VALIDATE_IP) !== false) {
            return $value;
        } else {
            return null;
        }
    }

    /**
     * @param string $value
     * @param array  $parameters
     *
     * @return int|null
     */
    protected function _filter_secint($value, $parameters)
    {
        $v = $this->secint->decode($value, isset($parameters[0]) ? $parameters[0] : '');
        if ($v === false) {
            return null;
        } else {
            return $v;
        }
    }

    /**
     * @param string $value
     * @param array  $parameters
     *
     * @return int|null
     */
    protected function _filter_timestamp($value, $parameters)
    {
        if (is_numeric($value)) {
            return (int)$value;
        } else {
            $r = strtotime($value);

            return $r === false ? null : $r;
        }
    }

    /**
     * @return array
     */
    public function dump()
    {
        $data = parent::dump();
        $data['_filters'] = array_keys($this->_filters);

        return $data;
    }

}