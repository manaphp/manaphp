<?php
namespace ManaPHP\Model;

use ManaPHP\Component;
use ManaPHP\Exception\FileNotFoundException;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Model\Validator\Message;
use ManaPHP\Model\Validator\ValidateFailedException;
use ManaPHP\Utility\Text;

/**
 * Class ManaPHP\Model\Validator
 *
 * @package ManaPHP\Model
 * @property \ManaPHP\Security\HtmlPurifierInterface $htmlPurifier
 */
class Validator extends Component implements ValidatorInterface
{
    /**
     * @var \ManaPHP\Model
     */
    protected $_model;

    /**
     * @var string
     */
    protected $_field;

    /**
     * @var string
     */
    protected $_templates_dir = '@manaphp/Model/Validator/Messages';

    /**
     * @var array
     */
    protected $_templates;

    /**
     * @var array
     */
    protected $_messages;

    /**
     * Validator constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['templates_dir'])) {
            $this->_templates_dir = $options['templates_dir'];
        }

        if (isset($options['templates'])) {
            $this->_templates = $options['templates'];
            $this->_templates_dir = null;
        }
    }

    public function saveInstanceState()
    {
        return true;
    }

    public function restoreInstanceState($data)
    {
        $this->_model = null;
        $this->_field = null;
    }

    /**
     * @return array
     */
    protected function _loadTemplates()
    {
        $languages = explode(',', $this->configure->language);
        $file = "{$this->_templates_dir}/$languages[0].php";
        if (!$this->filesystem->fileExists($file)) {
            throw new FileNotFoundException(['`:file` validator message template file is not exists', 'file' => $file]);
        }
        /** @noinspection PhpIncludeInspection */
        return $this->_templates = require $this->alias->resolve($file);
    }

    /**
     * @param \ManaPHP\Model\Validator\Message $message
     *
     * @return static
     */
    public function appendMessage($message)
    {
        $this->_messages[$message->field][] = $message;

        return $this;
    }

    /**
     * @param string $field
     *
     * @return \ManaPHP\Model\Validator\Message[]
     */
    public function getMessages($field = null)
    {
        if ($field) {
            return isset($this->_messages[$field]) ? $this->_messages[$field] : [];
        } else {
            return $this->_messages;
        }
    }

    /**
     * @param \ManaPHP\Model $model
     * @param array          $fields
     *
     * @return void
     * @throws \ManaPHP\Model\Validator\ValidateFailedException
     */
    public function validate($model, $fields = [])
    {
        $this->_messages = [];

        if (!$rules = $model->rules()) {
            return;
        }

        $this->_model = $model;

        $templates = null;
        foreach ($fields ?: $model->getChangedFields() as $field) {
            if (!isset($rules[$field])) {
                continue;
            }
            $this->_field = $field;

            foreach ((array)$rules[$field] as $k => $v) {
                if (is_int($k)) {
                    $name = $v;
                    $parameters = null;
                } else {
                    $name = $k;
                    $parameters = $v;
                }

                $value = $this->_validate($model->$field, $name, $parameters);
                if ($value === null) {
                    $templates = $this->_templates ?: $this->_loadTemplates();
                    $template = isset($templates[$name]) ? $templates[$name] : $templates['default'];
                    $this->appendMessage(new Message($template, $this->_model, $this->_field, $parameters));
                } else {
                    $model->$field = $value;
                }
            }
        }

        if ($this->_messages) {
            throw new ValidateFailedException('validate failed: ' . json_encode($this->_messages));
        }
    }

    /**
     * @param string|int|object $value
     * @param string            $name
     * @param string|array      $parameters
     *
     * @return mixed
     */
    protected function _validate($value, $name, $parameters)
    {
        if (is_object($value)) {
            return $value;
        }

        $method = "_validate_$name";
        if (method_exists($this, $method)) {
            return $parameters === null ? $this->$method($value) : $this->$method($value, $parameters);
        }

        if (function_exists($name)) {
            return $parameters === null ? $name($value) : $name($value, $parameters);
        }

        throw new NotSupportedException(['unsupported `:validate` validate method', 'validate' => $name]);
    }

    /**
     * @param string $value
     *
     * @return null|string
     */
    protected function _validate_required($value)
    {
        return $value !== null && $value !== '' ? $value : null;
    }

    /**
     * @param string|bool $value
     *
     * @return int|null
     */
    protected function _validate_bool($value)
    {
        if (is_bool($value)) {
            return (int)$value;
        }

        $trueValues = ['1', 'true', 'on'];
        $falseValues = ['0', 'false', 'off'];

        $value = strtolower($value);

        if (in_array($value, $trueValues, true)) {
            return 1;
        } elseif (in_array($value, $falseValues, true)) {
            return 0;
        } else {
            return null;
        }
    }

    /**
     * @param string|int $value
     *
     * @return int|null
     */
    protected function _validate_int($value)
    {
        if (is_int($value)) {
            return $value;
        }

        return preg_match('#^[+-]?\d+$#', $value) ? (int)$value : null;
    }

    /**
     * @param string|float|int $value
     *
     * @return float|null
     */
    protected function _validate_float($value)
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

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
     * @param string $parameter
     *
     * @return int|null
     */
    protected function _validate_date($value, $parameter = null)
    {
        $ts = is_numeric($value) ? (int)$value : strtotime($value);
        if ($ts === false) {
            return null;
        }

        if ($format = $this->_model->getDateFormat($this->_field)) {
            $r = date($parameter ?: $format, $ts);
            return $r !== false ? $r : null;
        } else {
            return $ts;
        }
    }

    /**
     * @param int|double $value
     * @param string     $parameter
     *
     * @return int|double|null
     */
    protected function _validate_range($value, $parameter)
    {
        if (preg_match('#^(-?[\.\d]+)-(-?[\d\.]+)$#', $parameter, $match)) {
            return $value >= $match[1] && $value <= $match[2] ? $value : null;
        } else {
            throw new InvalidValueException(['range validator `:parameter` parameters is not {min}-{max} format', 'parameter' => $parameter]);
        }
    }

    /**
     * @param int|double $value
     * @param int|float  $parameter
     *
     * @return int|double|null
     */
    protected function _validate_min($value, $parameter)
    {
        return $value < $parameter ? null : $value;
    }

    /**
     * @param int|double $value
     * @param int|double $parameter
     *
     * @return int|double|null
     */
    protected function _validate_max($value, $parameter)
    {
        return $value > $parameter ? null : $value;
    }

    /**
     * @param int|double $value
     * @param string     $parameter
     *
     * @return int|double|null
     */
    protected function _validate_length($value, $parameter)
    {
        $len = mb_strlen($value);
        if (preg_match('#^(\d+)-(\d+)$#', $parameter, $match)) {
            return $len >= $match[1] && $len <= $match[2] ? $value : null;
        } else {
            throw new InvalidValueException(['length validator `:parameter` parameters is not {minLength}-{maxLength} format', 'parameter' => $parameter]);
        }
    }

    /**
     * @param string|int $value
     * @param string     $parameter
     *
     * @return string|int
     */
    protected function _validate_in($value, $parameter)
    {
        return in_array($value, preg_split('#[\s,]+#', $parameter, -1, PREG_SPLIT_NO_EMPTY), false) ? $value : null;
    }

    /**
     * @param string|int $value
     * @param string     $parameter
     *
     * @return string|int
     */
    protected function _validate_not_in($value, $parameter)
    {
        return !in_array($value, preg_split('#[\s,]+#', $parameter, -1, PREG_SPLIT_NO_EMPTY), false) ? $value : null;
    }

    /**
     * @param string $value
     * @param string $parameter
     *
     * @return string|null
     */
    protected function _validate_regex($value, $parameter)
    {
        return ($value === '' || preg_match($parameter, $value)) ? $value : null;
    }

    /**
     * @param string $value
     *
     * @return string|null
     */
    protected function _validate_alpha($value)
    {
        return preg_match('#^[a-zA-Z]*$#', $value) ? $value : null;
    }

    /**
     * @param string $value
     *
     * @return string|null
     */
    protected function _validate_digit($value)
    {
        return preg_match('#^\d*$#', $value) ? $value : null;
    }

    /**
     * @param string $value
     *
     * @return string|null
     */
    protected function _validate_xdigit($value)
    {
        return preg_match('#^[0-9a-fA-F]*$#', $value) ? $value : null;
    }

    /**
     * @param string $value
     *
     * @return string|null
     */
    protected function _validate_alnum($value)
    {
        return preg_match('#^[a-zA-Z0-9]*$#', $value) ? $value : null;
    }

    /**
     * @param string $value
     *
     * @return null|string
     */
    protected function _validate_email($value)
    {
        return ($value === '' || filter_var($value, FILTER_VALIDATE_EMAIL) !== false) ? strtolower($value) : null;
    }

    /**
     * @param string $value
     * @param string $parameter
     *
     * @return null|string
     */
    protected function _validate_ext($value, $parameter)
    {
        if ($value === '') {
            return '';
        }

        $ext = pathinfo($value, PATHINFO_EXTENSION);
        return in_array(strtolower($ext), preg_split('#[\s,]+#', $parameter, -1, PREG_SPLIT_NO_EMPTY), true) ? $value : null;
    }

    /**
     * @param string $value
     *
     * @return null|string
     */
    protected function _validate_url($value)
    {
        if ($value === '') {
            return '';
        }

        if (strpos($value, '://') === false) {
            $value = 'http://' . $value;
        }

        return filter_var($value, FILTER_VALIDATE_URL) !== false ? $value : null;
    }

    /**
     * @param string $value
     *
     * @return null|string
     */
    protected function _validate_ip($value)
    {
        return ($value === '' || filter_var($value, FILTER_VALIDATE_IP) !== false) ? $value : null;
    }

    /**
     * @param string $value
     *
     * @return string
     */
    protected function _validate_escape($value)
    {
        return htmlspecialchars($value);
    }

    /**
     * @param string|int $value
     *
     * @return int|string|null
     */
    protected function _validate_unique($value)
    {
        $modelName = $this->_model;
        return ($value && $modelName::exists([$this->_field => $value])) ? null : $value;
    }

    /**
     * @param string $value
     * @param string $parameter
     *
     * @return string|null
     */
    protected function _validate_exists($value, $parameter = null)
    {
        if (!$value) {
            return $value;
        }
        $field = $this->_field;
        if ($parameter) {
            $className = $parameter;
        } else {
            $modelName = get_class($this->_model);
            if (preg_match('#^(.*)_id$#', $field, $match)) {
                $className = substr($modelName, 0, strrpos($modelName, '\\') + 1) . Text::camelize($match[1]);
                if (!class_exists($className)) {
                    $className = $this->alias->resolve('@ns.app\\Models\\' . Text::camelize($match[1]));
                }
            } else {
                throw new InvalidValueException(['validate `:field` field failed: related model class name is not provided', 'field' => $field]);
            }
        }

        if (!class_exists($className)) {
            throw new InvalidValueException(['validate `:field` field failed: related `:model` model class is not exists.', 'field' => $field, 'model' => $className]);
        }

        /**
         * @var \ManaPHP\Model $model
         * @var \ManaPHP\Model $className
         */
        $model = new $className;
        return $className::exists([$model->getPrimaryKey() => $value]) ? $value : null;
    }

    /**
     * @param string $value
     * @param string $parameter
     *
     * @return int|string|null
     */
    protected function _validate_const($value, $parameter = null)
    {
        $constants = $this->_model->getConstants($parameter ?: $this->_field);
        if (isset($constants[$value])) {
            return $value;
        } else {
            return ($r = array_search($value, $constants, true)) !== false ? $r : null;
        }
    }

    /**
     * @param string $value
     *
     * @return string
     */
    public function _validate_lower($value)
    {
        return strtolower($value);
    }

    /**
     * @param string $value
     *
     * @return string
     */
    public function _validate_upper($value)
    {
        return strtoupper($value);
    }
}