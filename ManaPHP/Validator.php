<?php
namespace ManaPHP;

use ManaPHP\Exception\FileNotFoundException;
use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Utility\Text;
use ManaPHP\Validator\ValidateFailedException;

/**
 * Class Validator
 * @package ManaPHP
 *
 * @property-read \ManaPHP\Security\HtmlPurifierInterface $htmlPurifier
 */
class Validator extends Component implements ValidatorInterface
{
    /**
     * @var string
     */
    protected $_dir = '@manaphp/Validator/Messages';

    /**
     * @var array
     */
    protected $_templates;

    /**
     * Validator constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        if (isset($options['dir'])) {
            $this->_dir = $options['dir'];
        }
    }

    /**
     * @return array
     */
    protected function _loadTemplates()
    {
        $languages = explode(',', $this->configure->language);
        $file = "{$this->_dir}/$languages[0].php";
        if (!$this->filesystem->fileExists($file)) {
            throw new FileNotFoundException(['`:file` validator message template file is not exists', 'file' => $file]);
        }
        /** @noinspection PhpIncludeInspection */
        return $this->_templates = require $this->alias->resolve($file);
    }

    /**
     * @param string               $field
     * @param \ManaPHP\Model|mixed $value
     * @param array|string         $rules
     *
     * @return mixed
     * @throws \ManaPHP\Validator\ValidateFailedException
     */
    public function validate($field, $value, $rules)
    {
        if ($value instanceof Model) {
            return $this->validateModel($field, $value, $rules);
        } else {
            return $this->validateValue($field, $value, $rules);
        }
    }

    /**
     * @param string $validate
     * @param string $field
     * @param mixed  $parameter
     *
     * @return \ManaPHP\Validator\ValidateFailedException
     */
    protected function _createValidateFailedException($validate, $field, $parameter = null)
    {
        $templates = $this->_templates ?: $this->_loadTemplates();
        $template = isset($templates[$validate]) ? $templates[$validate] : $templates['default'];
        $tr = [':field' => $field];

        if (is_string($template)) {
            $tr[':parameter'] = $parameter;
            $error = strtr($template, $tr);
        } else {
            $error = $template($field, $parameter);
        }

        return new ValidateFailedException([$field => $error]);
    }

    /**
     * @param string         $field
     * @param \ManaPHP\Model $model
     * @param array          $rules
     *
     * @return mixed
     */
    public function validateModel($field, $model, $rules)
    {
        $value = $model->$field;

        if ($value === '' || $value === null) {
            if (isset($rules['default'])) {
                return $model->$field = $rules['default'];
            } else {
                throw $this->_createValidateFailedException('required', $field);
            }
        }

        foreach ((array)$rules as $k => $v) {
            if (is_int($k)) {
                if (strpos($v, '-') !== false) {
                    $validate = in_array($field, $model->getIntFields(), true) ? 'range' : 'length';
                    $parameter = $v;
                } else {
                    $validate = $v;
                    $parameter = null;
                }
            } else {
                $validate = $k;
                $parameter = $v;
            }

            if (method_exists($this, $method = '_validate_model_' . $validate)) {
                $value = $parameter === null ? $this->$method($model, $field) : $this->$method($model, $field, $parameter);
            } elseif (method_exists($this, $method = '_validate_' . $validate)) {
                $value = $parameter === null ? $this->$method($value) : $this->$method($value, $parameter);
            } else {
                throw new NotSupportedException(['unsupported `:validate` validate method', 'validate' => $validate]);
            }

            if ($value === null) {
                throw $this->_createValidateFailedException($validate, $field, $parameter);
            }
        }

        return $model->$field = $value;
    }

    /**
     * @param string               $field
     * @param \ManaPHP\Model|mixed $value
     * @param array                $rules
     *
     * @return mixed
     * @throws \ManaPHP\Validator\ValidateFailedException
     */
    public function validateValue($field, $value, $rules)
    {
        if ($value === '' || $value === null) {
            if (isset($rules['default'])) {
                return $rules['default'];
            } else {
                throw $this->_createValidateFailedException('required', $field, $value);
            }
        }

        $rules = (array)$rules;
        foreach ($rules as $k => $v) {
            if (is_int($k)) {
                if (strpos($v, '-') !== false) {
                    $parameter = $v;
                    if (in_array('string', $rules, true)) {
                        $validate = 'length';
                    } elseif (in_array('int', $rules, true) || in_array('float', $rules, true)) {
                        $validate = 'range';
                    } elseif (isset($rules['default'])) {
                        $validate = is_string($rules['default']) ? 'length' : 'range';
                    } else {
                        throw new InvalidArgumentException('');
                    }
                } else {
                    $validate = $v;
                    $parameter = null;
                }
            } else {
                $validate = $k;
                $parameter = $v;
            }

            if (method_exists($this, $method = '_validate_' . $validate)) {
                $value = $parameter === null ? $this->$method($value) : $this->$method($value, $parameter);
            } else {
                throw new NotSupportedException(['unsupported `:validate` validate method', 'validate' => $validate]);
            }

            if ($value === null) {
                throw $this->_createValidateFailedException($validate, $field, $parameter);
            }
        }

        return $value;
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
     * @param mixed $value
     *
     * @return mixed
     */
    protected function _validate_default($value)
    {
        return $value;
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

        if (strpos(',1,true,on,yes,', ",$value,") !== false) {
            return 1;
        } elseif (strpos(',0,false,off,no,', ",$value,") !== false) {
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
     * @param mixed $value
     *
     * @return string
     */
    protected function _validate_string($value)
    {
        return (string)$value;
    }

    /**
     * @param int|float $value
     * @param int|float $parameter
     *
     * @return int|float|null
     */
    protected function _validate_min($value, $parameter)
    {
        return $value < $parameter ? null : $value;
    }

    /**
     * @param int|float $value
     * @param int|float $parameter
     *
     * @return int|float|null
     */
    protected function _validate_max($value, $parameter)
    {
        return $value > $parameter ? null : $value;
    }

    /**
     * @param int|float $value
     * @param string    $parameter
     *
     * @return int|float|null
     */
    protected function _validate_length($value, $parameter)
    {
        $len = mb_strlen($value);
        if (preg_match('#^(\d+)-(\d+)$#', $parameter, $match)) {
            return $len >= $match[1] && $len <= $match[2] ? $value : null;
        } else {
            throw new InvalidValueException(['length validator `:parameter` parameter is not {minLength}-{maxLength} format', 'parameter' => $parameter]);
        }
    }

    /**
     * @param string  $value
     * @param integer $parameter
     *
     * @return string|null
     */
    protected function _validate_minLength($value, $parameter)
    {
        return mb_strlen($value) >= $parameter ? $value : null;
    }

    /**
     * @param string $value
     * @param string $parameter
     *
     * @return string|null
     */
    protected function _validate_maxLength($value, $parameter)
    {
        return mb_strlen($value) <= $parameter ? $value : null;
    }

    /**
     * @param int|float $value
     * @param string    $parameter
     *
     * @return int|float|null
     */
    protected function _validate_range($value, $parameter)
    {
        if (!is_int($value) && !is_float($value)) {
            $value = strpos($parameter, '.') === false ? $this->_validate_int($value) : $this->_validate_float($value);
            if ($value === null) {
                return null;
            }
        }
	
        if (preg_match('#^(-?[\.\d]+)-(-?[\d\.]+)$#', $parameter, $match)) {
            return $value >= $match[1] && $value <= $match[2] ? $value : null;
        } else {
            throw new InvalidValueException(['range validator `:parameter` parameter is not {min}-{max} format', 'parameter' => $parameter]);
        }
    }

    /**
     * @param string $value
     * @param string $parameter
     *
     * @return string|null
     */
    protected function _validate_regex($value, $parameter)
    {
        return preg_match($parameter, $value) ? $value : null;
    }

    /**
     * @param string $value
     *
     * @return string|null
     */
    protected function _validate_alpha($value)
    {
        return preg_match('#^[a-zA-Z]+$#', $value) ? $value : null;
    }

    /**
     * @param string $value
     *
     * @return string|null
     */
    protected function _validate_digit($value)
    {
        return preg_match('#^\d+$#', $value) ? $value : null;
    }

    /**
     * @param string $value
     *
     * @return string|null
     */
    protected function _validate_xdigit($value)
    {
        return preg_match('#^[0-9a-fA-F]+$#', $value) ? $value : null;
    }

    /**
     * @param string $value
     *
     * @return string|null
     */
    protected function _validate_alnum($value)
    {
        return preg_match('#^[a-zA-Z0-9]+$#', $value) ? $value : null;
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

    public function _validate_trim($value)
    {
        return trim($value);
    }

    /**
     * @param string $value
     *
     * @return null|string
     */
    protected function _validate_email($value)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false ? strtolower($value) : null;
    }

    /**
     * @param string $value
     *
     * @return null|string
     */
    protected function _validate_url($value)
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false ? $value : null;
    }

    /**
     * @param string $value
     *
     * @return null|string
     */
    protected function _validate_ip($value)
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false ? $value : null;
    }

    /**
     * @param string      $value
     * @param null|string $parameter
     *
     * @return string|int
     */
    protected function _validate_date($value, $parameter = null)
    {
        $ts = is_numeric($value) ? (int)$value : strtotime($value);
        if ($ts === false) {
            return null;
        }

        return $parameter ? date($parameter, $ts) : $value;
    }

    /**
     * @param \ManaPHP\Model $model
     * @param string         $field
     * @param null|string    $parameter
     *
     * @return string|int
     */
    protected function _validate_model_date($model, $field, $parameter)
    {
        $value = $model->$field;

        if (($ts = is_numeric($value) ? (int)$value : strtotime($value)) === false) {
            return null;
        }

        return date($parameter ?: $model->getDateFormat($field), $ts);
    }

    /**
     * @param string $value
     *
     * @return string
     */
    public function _validate_escape($value)
    {
        return htmlspecialchars($value);
    }

    /**
     * @param string $value
     *
     * @return string
     */
    protected function _validate_xss($value)
    {
        if ($value === '') {
            return $value;
        } else {
            return $this->htmlPurifier->purify($value);
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
     * @param string       $value
     * @param string|array $parameter
     *
     * @return null|string
     */
    protected function _validate_ext($value, $parameter)
    {
        $ext = strtolower(pathinfo($value, PATHINFO_EXTENSION));
        if (is_array($parameter)) {
            return in_array($ext, $parameter, true) ? $value : null;
        } else {
            return in_array($ext, preg_split('#[\s,]+#', $parameter, -1, PREG_SPLIT_NO_EMPTY), true) ? $value : null;
        }
    }


    /**
     * @param \ManaPHP\Model $model
     * @param string|int     $field
     *
     * @return int|string|null
     */
    protected function _validate_model_unique($model, $field)
    {
        $value = $model->$field;
        return $model::exists([$field => $value]) ? null : $value;
    }

    /**
     * @param \ManaPHP\Model $model
     * @param string         $field
     * @param string         $parameter
     *
     * @return string|null
     */
    protected function _validate_model_exists($model, $field, $parameter = null)
    {
        $value = $model->$field;

        if (!$value) {
            return $value;
        }

        if ($parameter) {
            $className = $parameter;
        } else {
            if (preg_match('#^(.*)_id$#', $field, $match)) {
                $modelName = get_class($model);
                $className = substr($modelName, 0, strrpos($modelName, '\\') + 1) . Text::camelize($match[1]);
                if (!class_exists($className)) {
                    $className = $this->alias->get('@ns.app') . '\\Models\\' . Text::camelize($match[1]);
                }
            } else {
                throw new InvalidValueException(['validate `:field` field failed: related model class name is not provided', 'field' => $field]);
            }
        }

        if (!class_exists($className)) {
            throw new InvalidValueException(['validate `:1` field failed: related `:2` model class is not exists.', $field, $className]);
        }

        return $className::exists($value) ? $value : null;
    }

    /**
     * @param \ManaPHP\Model $model
     * @param string         $field
     * @param string         $parameter
     *
     * @return int|string|null
     */
    protected function _validate_model_const($model, $field, $parameter = null)
    {
        $value = $model->$field;
        $constants = $model::consts($parameter ?: $field);
        if (isset($constants[$value])) {
            return $value;
        } else {
            return ($r = array_search($value, $constants, true)) !== false ? $r : null;
        }
    }

    public function _validate_account($value)
    {
        $value = strtolower($value);

        if (!preg_match('#^[a-z][a-z0-9_]{2,}$#', $value)) {
            return null;
        }

        if (strpos($value, '__') !== false) {
            return null;
        }

        return $value;
    }

    /**
     * @param string $value
     *
     * @return string|null
     */
    protected function _validate_mobile($value)
    {
        $value = trim($value);

        return ($value === '' || preg_match('#^1[3-8]\d{9}$#', $value)) ? $value : null;
    }
}