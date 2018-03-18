<?php
namespace ManaPHP\Model;

use ManaPHP\Component;
use ManaPHP\Model\Validator\Exception as ValidatorException;
use ManaPHP\Model\Validator\Message;
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
    protected $templates_dir = '@manaphp/Models/Validator/Messages';

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
            $this->templates_dir = $options['templates_dir'];
        }

        if (isset($options['templates'])) {
            $this->_templates = $options['templates'];
            $this->templates_dir = null;
        }
    }

    /**
     * @return array
     */
    protected function _loadTemplates()
    {
        $languages = explode(',', $this->configure->language);
        $file = "{$this->templates_dir}/$languages[0].php";
        if (!$this->filesystem->fileExists($file)) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new ValidatorException(['`:file` validator message template file is not exists'/**m08523be1bf26d3984*/, 'file' => $file]);
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
     * @throws \ManaPHP\Model\Validator\Exception
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
                    $parameters = (array)$v;
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
            throw new ValidatorException('validate failed: ' . json_encode($this->_messages));
        }
    }

    /**
     * @param string|int $value
     * @param string     $name
     * @param array      $params
     *
     * @return mixed
     */
    protected function _validate($value, $name, $params)
    {
        $method = "_validate_$name";
        if (method_exists($this, $method)) {
            return $params === null ? $this->$method($value) : $this->$method($value, $params);
        }

        if (function_exists($name)) {
            return $params === null ? $name($value) : $name($value, $params);
        }

        /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
        throw new ValidatorException(['unsupported `:validate` validate method', 'validate' => $name]);
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
     * @return bool|null
     */
    protected function _validate_bool($value)
    {
        if (is_bool($value)) {
            return $value;
        }

        $trueValues = ['1', 'true', 'on'];
        $falseValues = ['0', 'false', 'off'];

        $value = strtolower($value);

        if (in_array($value, $trueValues, true)) {
            return true;
        } elseif (in_array($value, $falseValues, true)) {
            return false;
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
     * @param array  $parameters
     *
     * @return int|null
     */
    protected function _validate_date($value, $parameters = [])
    {
        $timestamp = is_numeric($value) ? (int)$value : strtotime($value);
        if ($timestamp === false) {
            return null;
        }

        if (in_array($this->_field, $this->_model->getIntTypeFields(), true)) {
            return $timestamp;
        } else {
            $format = isset($parameters[0]) ? $parameters[0] : 'Y-m-d H:i:s';

            $r = date($format, $timestamp);
            return $r !== false ? $r : null;
        }
    }

    /**
     * @param int|double $value
     * @param array      $parameters
     *
     * @return int|double|null
     */
    protected function _validate_range($value, $parameters)
    {
        return $value > $parameters[0] && $value < $parameters[1] ? $value : null;
    }

    /**
     * @param int|double $value
     * @param array      $parameters
     *
     * @return int|double|null
     */
    protected function _validate_min($value, $parameters)
    {
        return $value < $parameters[0] ? null : $value;
    }

    /**
     * @param int|double $value
     * @param array      $parameters
     *
     * @return int|double|null
     */
    protected function _validate_max($value, $parameters)
    {
        return $value > $parameters[0] ? null : $value;
    }

    /**
     * @param string|int $value
     * @param array      $parameters
     *
     * @return string|int
     */
    protected function _validate_in($value, $parameters)
    {
        return in_array($value, preg_split('#[\s,]+#', $parameters[0]), false) ? $value : null;
    }

    /**
     * @param string|int $value
     * @param array      $parameters
     *
     * @return string|int
     */
    protected function _validate_not_in($value, $parameters)
    {
        return !in_array($value, preg_split('#[\s,]+#', $parameters[0]), false) ? $value : null;
    }

    /**
     * @param string $value
     * @param array  $parameters
     *
     * @return string|null
     */
    protected function _validate_regex($value, $parameters)
    {
        return ($value === '' || preg_match($parameters[0], $value)) ? $value : null;
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
        $value = trim($value);

        return ($value === '' || filter_var($value, FILTER_VALIDATE_EMAIL) !== false) ? strtolower($value) : null;
    }

    /**
     * @param string $value
     * @param array  $parameters
     *
     * @return null|string
     */
    protected function _validate_ext($value, $parameters)
    {
        if ($value === '') {
            return '';
        }

        $ext = pathinfo($value, PATHINFO_EXTENSION);
        return in_array(strtolower($ext), preg_split('#[\s,]+#', $parameters[0]), true) ? $value : null;
    }

    /**
     * @param string $value
     *
     * @return null|string
     */
    protected function _validate_url($value)
    {
        $value = trim($value);

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
        $value = trim($value);

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
        return $value && $modelName::exists([$this->_field => $value]) ? null : $value;
    }

    /**
     * @param string $value
     * @param array  $parameters
     *
     * @return string|null
     */
    protected function _validate_exists($value, $parameters = [])
    {
        if (!$value) {
            return $value;
        }
        $field = $this->_field;
        if ($parameters) {
            $className = $parameters[0];
        } else {
            $modelName = get_class($this->_model);
            if (preg_match('#^(.*)_id$#', $field, $match)) {
                $className = substr($modelName, 0, strrpos($modelName, '\\') + 1) . Text::camelize($match[1]);
                if (!class_exists($className)) {
                    $className = $this->alias->resolve('@ns.app\\Models\\' . Text::camelize($match[1]));
                }
            } else {
                /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
                throw new ValidatorException(['validate `:field` field failed: related model class name is not provided', 'field' => $field]);
            }
        }

        if (!class_exists($className)) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new ValidatorException(['validate `:field` field failed: related `:model` model class is not exists.', 'field' => $field, 'model' => $className]);
        }

        /**
         * @var \ManaPHP\Model $model
         */
        $model = new $className;
        return $className::exists([$model->getPrimaryKey() => $value]) ? $value : null;
    }

    public function reConstruct()
    {
        if ($this->templates_dir && strpos($this->configure->language, ',') !== false) {
            $this->_templates = null;
        }
    }
}