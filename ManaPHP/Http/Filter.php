<?php
namespace ManaPHP\Http {

    use ManaPHP\Component;
    use ManaPHP\Http\Filter\Exception;
    use ManaPHP\Utility\Text;

    class Filter extends Component implements FilterInterface
    {
        /**
         * @var array
         */
        protected $_methods = [];

        /**
         * @var array
         */
        protected $_messages = [];

        protected $_defaultMessage ='The value of :attribute is invalid.';
        /**
         * Filter constructor.
         *
         * @param array $options
         */
        public function __construct($options=[])
        {
            parent::__construct();

            foreach (get_class_methods($this) as $method) {
                if (Text::startsWith($method, '_method_')) {
                    $this->_methods[substr($method, 8)] = [$this, $method];
                }
            }

            $this->_messages=$this->_defaultMessages();

            if(is_object($options)){
                $options=(array)$options;
            }

            if(isset($options['messages'])){
                $this->_messages=array_merge($this->_messages,$options['messages']);
            }

            if(isset($options['defaultMessage'])){
                $this->_defaultMessage=$options['defaultMessage'];
            }
        }

        protected function _defaultMessages(){
            return [
                'required'=>':attribute is required.',
                'email'=>'Please enter a valid email address.',
                'url'=>'Please enter a valid URL',
                'date'=>'Please enter a valid date.',
                'number'=>'Please enter a valid number.',
                'digit'=>'Please enter only digits.',
                'equal'=>'Please enter a valid value.',
                'maxLength'=>'Please enter no more than :parameter[0], characters.'
            ];
        }

        /**
         * @param string   $name
         * @param callable $method
         *
         * @return mixed
         */
        public function add($name, $method)
        {
            $this->_methods[$name] = $method;
        }

        /**
         * @param string $rule
         *
         * @return array
         */
        protected function _parseRule($rule)
        {
            $parts = explode('|', $rule);

            $items = [];
            foreach ($parts as $part) {
                if (Text::contains($part, ':')) {
                    list($name, $parameter) = explode(':', $part);
                    $parameters = explode(',', $parameter);
                } else {
                    $name = $part;
                    $parameters = [];
                }

                $items[$name] = $parameters;
            }

            return $items;
        }

        protected function _getError($attribute, $value, $rule, $parameters)
        {
            $replaces=[];

            $replaces[':attribute']=$attribute;
            $replaces[':value']=$value;
            foreach ($parameters as $k=>$parameter){
                $replaces[':parameter['.$k.']']=$parameter;
            }

            if(isset($this->_messages[$rule])){
                $message=$this->_messages[$rule];
            }else{
                $message =$this->_defaultMessage;
            }

            return strtr($message,$replaces);
        }

        /**
         * @param string                   $attribute
         * @param string|int|boolean       $rule
         * @param string|int|boolean|array $value
         *
         * @return mixed
         * @throws \ManaPHP\Http\Exception
         */
        public function sanitize($attribute, $rule, $value)
        {
            if (is_int($value)) {
                $value = (string)$value;
            } elseif (is_bool($value)) {
                $value = (string)(int)$value;
            }

            foreach ($this->_parseRule($rule) as $name => $parameters) {
                if (isset($this->_methods[$name])) {
                    $method = $this->_methods[$name];
                } elseif (function_exists($name)) {
                    $method = $name;
                } else {
                    throw new \ManaPHP\Http\Exception('filter `' . $name . '` is not be recognized.');
                }

                $value = call_user_func_array($method, [$value, $parameters]);
                if ($value === null) {
                    $error = $this->_getError($attribute, $value, $name, $parameters);

                    throw new Exception($error);
                }
            }

            return $value;
        }

        protected function _method_required($value){
            if($value===null){
                return null;
            }else{
                return $value;
            }
        }

        /**
         * @param string $value
         *
         * @return boolean|null
         */
        protected function _method_boolean($value)
        {
            if (in_array($value, ['1', 'true'], true)) {
                return true;
            } elseif (in_array($value, ['0', 'false'], true)) {
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
        protected function _method_int($value)
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
        protected function _method_float($value)
        {
            if (filter_var($value, FILTER_VALIDATE_FLOAT) !== false) {
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
        protected function _method_date($value)
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
        protected function _method_range($value, $parameters)
        {
            $value = $this->_method_float($value);
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
        protected function _method_minValue($value, $parameters)
        {
            $value = $this->_method_float($value);
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
        protected function _method_maxValue($value, $parameters)
        {
            $value = $this->_method_float($value);
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
        protected function _method_maxLength($value, $parameters)
        {
            if (strlen($value) <= $parameters[0]) {
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
        protected function _method_minLength($value, $parameters)
        {
            if (strlen($value) >= $parameters[0]) {
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
        protected function _method_length($value, $parameters)
        {
            $strLength = strlen($value);
            if ($strLength >= $parameters[0] && $strLength <= $parameters[1]) {
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
        protected function _method_equal($value, $parameters)
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
        protected function _method_regex($value, $parameters)
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
        protected function _method_alpha($value)
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
        protected function _method_digit($value)
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
        protected function _method_alnum($value)
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
        protected function _method_lower($value)
        {
            return strtolower($value);
        }

        /**
         * @param string $value
         *
         * @return string
         */
        protected function _method_upper($value)
        {
            return strtoupper($value);
        }

        /**
         * @param string $value
         *
         * @return string|null
         */
        protected function _method_account($value)
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
        protected function _method_password($value)
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
        protected function _method_email($value)
        {
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
        protected function _method_url($value)
        {
            if (filter_var($value, FILTER_VALIDATE_URL) !== false) {
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
        protected function _method_in($value, $parameters)
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
        protected function _method_not_in($value, $parameters)
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
        protected function _method_json($value)
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
        protected function _method_mobile($value)
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
         * @return false
         */
        protected function _method_captcha($value)
        {
            if ($this->captcha->verify($value)) {
                return true;
            } else {
                return false;
            }
        }

        /**
         * @param string $value
         *
         * @return string|null
         */
        protected function _method_ip($value)
        {
            if (filter_var($value, FILTER_VALIDATE_IP) !== false) {
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
        protected function _method_accepted($value)
        {
            if (in_array($value, ['yes', 'on', '1', 'true'], true)) {
                return true;
            } elseif (in_array($value, ['no', 'off', '0', 'false'], true)) {
                return false;
            } else {
                return null;
            }
        }
    }
}
