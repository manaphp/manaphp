<?php
namespace ManaPHP\Http;

use ManaPHP\Component;
use ManaPHP\Http\Filter\Exception;
use ManaPHP\Utility\Text;

class Filter extends Component implements FilterInterface
{
    /**
     * @var array
     */
    protected $_rules = [];

    /**
     * @var array
     */
    protected $_messages = [];

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
     * @var string
     */
    protected $_messagesFile;

    /**
     * Filter constructor.
     *
     * @param array $options
     *
     * @throws \ManaPHP\Http\Exception
     */
    public function __construct($options = [])
    {
        foreach (get_class_methods($this) as $method) {
            if (Text::startsWith($method, '_rule_')) {
                $this->_rules[substr($method, 6)] = [$this, $method];
            }
        }

        if (is_object($options)) {
            $options = (array)$options;
        }

        if (!isset($options['messages'])) {
            $options['messages'] = 'en';
        }

        if (is_string($options['messages'])) {
            if (Text::contains($options['messages'], '.')) {
                $this->_messagesFile = $options['messages'];
            } else {
                $this->_messagesFile = '@manaphp/Http/Filter/Messages/' . $options['messages'] . '.php';
            }
        }
        $this->_messages = $options['messages'];

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
    public function addRule($name, $method)
    {
        $this->_rules[$name] = $method;

        return $this;
    }

    /**
     * @param array $attributes
     *
     * @return static
     */
    public function addAttributes($attributes)
    {
        $this->_attributes = array_merge($this->_attributes, $attributes);

        return $this;
    }

    /**
     * @param string $rules
     *
     * @return array
     */
    protected function _parseRules($rules)
    {
        $parts = (array)explode('|', $rules);

        $items = [];
        foreach ($parts as $part) {
            if (Text::contains($part, ':')) {
                $parts2 = explode(':', $part);
                $name = $parts2[0];
                $parameters = explode(',', $parts2[1]);
            } else {
                $name = $part;
                $parameters = [];
            }

            $name = trim($name);
            if ($name === '') {
                continue;
            }

            $items[$name] = $parameters;
        }

        return $items;
    }

    protected function _getError($attribute, $value, $rule, $parameters)
    {
        if (count($this->_messages) === 0) {
            $file = $this->alias->resolve($this->_messagesFile);

            if (!is_file($file)) {
                throw new \ManaPHP\Http\Exception('filter message template file is not exists: ' . $file);
            }

            /** @noinspection PhpIncludeInspection */
            $options['messages'] = require $file;
        }

        $replaces = [];

        $replaces[':attribute'] = isset($this->_attributes[$attribute]) ? $this->_attributes[$attribute] : $attribute;
        $replaces[':value'] = $value;
        foreach ($parameters as $k => $parameter) {
            $replaces[':parameter[' . $k . ']'] = $parameter;
        }

        if (isset($this->_messages[$rule])) {
            $message = $this->_messages[$rule];
        } else {
            $message = $this->_defaultMessage;
        }

        return strtr($message, $replaces);
    }

    /**
     * @param string                   $attribute
     * @param string                   $rules
     * @param string|int|boolean|array $value
     *
     * @return mixed
     * @throws \ManaPHP\Http\Exception
     */
    public function sanitize($attribute, $rules, $value)
    {
        if (is_int($value)) {
            $value = (string)$value;
        } elseif (is_bool($value)) {
            $value = $value ? '1' : '0';
        } elseif ($value === null) {
            $value = '';
        }

        $ruleItems = $this->_parseRules($rules);
        foreach ($ruleItems as $name => $parameters) {
            $value = $this->_sanitize($attribute, $name, $parameters, $value);
        }

        if (is_string($value) && !isset($ruleItems['ignore']) && !isset($ruleItems['xss'])) {
            $parameters = [];
            $value = $this->_sanitize($attribute, 'xss', $parameters, $value);
        }

        return $value;
    }

    protected function _sanitize($attribute, $name, $parameters, $value)
    {
        if (isset($this->_rules[$name])) {
            $method = $this->_rules[$name];
        } elseif (function_exists($name)) {
            $method = $name;
        } else {
            throw new \ManaPHP\Http\Exception('filter `' . $name . '` is not be recognized.');
        }

        $callParameter = [$value, $parameters];
        $value = call_user_func_array($method, $callParameter);
        if ($value === null) {
            $error = $this->_getError($attribute, $value, $name, $parameters);

            throw new Exception($error);
        }

        return $value;
    }

    /**
     * @param string $value
     *
     * @return string|null
     */
    protected function _rule_required($value)
    {
        if ($value === '') {
            return null;
        } else {
            return $value;
        }
    }

    protected function _rule_default($value, $parameters)
    {
        if ($value === '' || $value === null) {
            return $parameters[0];
        } else {
            return $value;
        }
    }

    protected function _rule_ignore($value)
    {
        return $value;
    }

    /**
     * @param string $value
     * @param array  $parameters
     *
     * @return string
     */
    protected function _rule_xss($value, $parameters)
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
     * @return boolean|null
     */
    protected function _rule_boolean($value)
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
    protected function _rule_int($value)
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
    protected function _rule_float($value)
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
     *
     * @return int|null
     */
    protected function _rule_date($value)
    {
        $timestamp = strtotime($value);
        if ($timestamp !== false) {
            return $timestamp;
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
    protected function _rule_range($value, $parameters)
    {
        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $value = $this->_rule_float($value);
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
    protected function _rule_min($value, $parameters)
    {
        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $value = $this->_rule_float($value);
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
    protected function _rule_max($value, $parameters)
    {
        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $value = $this->_rule_float($value);
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
    protected function _rule_minLength($value, $parameters)
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
    protected function _rule_maxLength($value, $parameters)
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
    protected function _rule_length($value, $parameters)
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
    protected function _rule_equal($value, $parameters)
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
    protected function _rule_regex($value, $parameters)
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
    protected function _rule_alpha($value)
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
    protected function _rule_digit($value)
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
    protected function _rule_alnum($value)
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
    protected function _rule_lower($value)
    {
        return strtolower($value);
    }

    /**
     * @param string $value
     *
     * @return string
     */
    protected function _rule_upper($value)
    {
        return strtoupper($value);
    }

    /**
     * @param string $value
     *
     * @return string|null
     */
    protected function _rule_account($value)
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
    protected function _rule_password($value)
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
    protected function _rule_email($value)
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
    protected function _rule_url($value)
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
    protected function _rule_in($value, $parameters)
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
    protected function _rule_not_in($value, $parameters)
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
    protected function _rule_json($value)
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
    protected function _rule_mobile($value)
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
    protected function _rule_ip($value)
    {
        $value = trim($value);

        if (filter_var($value, FILTER_VALIDATE_IP) !== false) {
            return $value;
        } else {
            return null;
        }
    }

    /**
     * @return array
     */
    public function dump()
    {
        $data = parent::dump();
        $data['_rules'] = array_keys($this->_rules);

        return $data;
    }
}