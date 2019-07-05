<?php /** @noinspection PhpUnusedParameterInspection */
namespace ManaPHP;

use Closure;
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
    protected $_dir = '@manaphp/Validator/Templates';

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
     * @param string $validate
     *
     * @return string|callable
     */
    protected function _getTemplate($validate)
    {
        $locate = strtolower($this->translator->getLocale());
        if (!isset($this->_templates[$locate])) {
            /** @noinspection PhpIncludeInspection */
            $this->_templates[$locate] = require $this->alias->resolve($this->_dir . "/$locate.php");
        }

        $templates = $this->_templates[$locate];
        return isset($templates[$validate]) ? $templates[$validate] : $templates['default'];
    }

    /**
     * @param string                $field
     * @param \ManaPHP\Model|mixed  $value
     * @param array|string|\Closure $rules
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
     * @return string
     */
    public function createError($validate, $field, $parameter = null)
    {
        $template = $this->_getTemplate($validate);
        $tr = [':field' => $field];

        if (is_string($template)) {
            $tr[':parameter'] = $parameter;
            return strtr($template, $tr);
        } else {
            return $template($field, $parameter);
        }
    }

    /**
     * @param string                $field
     * @param \ManaPHP\Model        $model
     * @param array|string|\Closure $rules
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
                throw new ValidateFailedException([$field => $this->createError('required', $field)]);
            }
        }

        foreach ((array)$rules as $k => $v) {
            if (is_int($k)) {
                if ($v instanceof Closure) {
                    $r = $v($value);
                    if ($r === null || $r === true) {
                        null;
                    } elseif ($r === false) {
                        throw new ValidateFailedException([$field => $this->createError('default', $field)]);
                    } else {
                        $value = $v;
                    }
                    continue;
                } elseif (strpos($v, '-') !== false) {
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
                $value = $parameter === null ? $this->$method($field, $model) : $this->$method($field, $model, $parameter);
            } elseif (method_exists($this, $method = '_validate_' . $validate)) {
                $value = $parameter === null ? $this->$method($field, $value) : $this->$method($field, $value, $parameter);
            } else {
                throw new NotSupportedException(['unsupported `:validate` validate method', 'validate' => $validate]);
            }

            if ($value === null) {
                throw new ValidateFailedException([$field => $this->createError($validate, $field, $parameter)]);
            }
        }

        return $model->$field = $value;
    }

    /**
     * @param string                $field
     * @param mixed                 $value
     * @param array|string|\Closure $rules
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
                throw new ValidateFailedException([$field => $this->createError('required', $field)]);
            }
        }

        $rules = (array)$rules;
        foreach ($rules as $k => $v) {
            if (is_int($k)) {
                if ($v instanceof Closure) {
                    $r = $v($value);
                    if ($r === null || $r === true) {
                        null;
                    } elseif ($r === false) {
                        throw new ValidateFailedException([$field => $this->createError('default', $field)]);
                    } else {
                        $value = $v;
                    }
                    continue;
                } elseif (strpos($v, '-') !== false) {
                    $parameter = $v;
                    if (in_array('string', $rules, true)) {
                        $validate = 'length';
                    } elseif (in_array('int', $rules, true) || in_array('float', $rules, true)) {
                        $validate = 'range';
                    } elseif (isset($rules['default'])) {
                        $validate = is_string($rules['default']) ? 'length' : 'range';
                    } else {
                        throw new InvalidArgumentException(['infer validate name failed: :value', 'value' => $v]);
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
                $value = $parameter === null ? $this->$method($field, $value) : $this->$method($field, $value, $parameter);
            } else {
                throw new NotSupportedException(['unsupported `:validate` validate method', 'validate' => $validate]);
            }

            if ($value === null) {
                throw new ValidateFailedException([$field => $this->createError($validate, $field, $parameter)]);
            }
        }

        return $value;
    }

    /**
     * @param string $field
     * @param string $value
     *
     * @return null|string
     */
    protected function _validate_required($field, $value)
    {
        return $value !== null && $value !== '' ? $value : null;
    }

    /**
     * @param string $field
     * @param mixed  $value
     *
     * @return mixed
     */
    protected function _validate_default($field, $value)
    {
        return $value;
    }

    /**
     * @param string      $field
     * @param string|bool $value
     *
     * @return int|null
     */
    protected function _validate_bool($field, $value)
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
     * @param string          $field
     * @param string|int|null $value
     *
     * @return int|null
     */
    protected function _validate_int($field, $value)
    {
        if (is_int($value)) {
            return $value;
        }

        return preg_match('#^[+\-]?\d+$#', $value) ? (int)$value : null;
    }

    /**
     * @param string           $field
     * @param string|float|int $value
     *
     * @return float|null
     */
    protected function _validate_float($field, $value)
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        if (filter_var($value, FILTER_VALIDATE_FLOAT) !== false
            && preg_match('#^[+\-]?[\d\.]+$#', $value) === 1
        ) {
            return (float)$value;
        } else {
            return null;
        }
    }

    /**
     * @param string $field
     * @param mixed  $value
     *
     * @return string
     */
    protected function _validate_string($field, $value)
    {
        return (string)$value;
    }

    /**
     * @param string         $field
     * @param int|float|null $value
     * @param int|float      $parameter
     *
     * @return int|float|null
     */
    protected function _validate_min($field, $value, $parameter)
    {
        if (!is_int($value) && !is_float($value) && ($value = $this->_validate_int($field, $value)) === null) {
            throw new ValidateFailedException([$field => $this->createError('int', $field)]);
        }

        return $value < $parameter ? null : $value;
    }

    /**
     * @param string         $field
     * @param int|float|null $value
     * @param int|float      $parameter
     *
     * @return int|float|null
     */
    protected function _validate_max($field, $value, $parameter)
    {
        if (!is_int($value) && !is_float($value) && ($value = $this->_validate_int($field, $value)) === null) {
            throw new ValidateFailedException([$field => $this->createError('int', $field)]);
        }

        return $value > $parameter ? null : $value;
    }

    /**
     * @param string    $field
     * @param int|float $value
     * @param string    $parameter
     *
     * @return int|float|null
     */
    protected function _validate_length($field, $value, $parameter)
    {
        $len = mb_strlen($value);
        if (preg_match('#^(\d+)-(\d+)$#', $parameter, $match)) {
            return $len >= $match[1] && $len <= $match[2] ? $value : null;
        } elseif (is_numeric($parameter)) {
            return $len === $parameter ? $value : null;
        } else {
            throw new InvalidValueException(['length validator `:parameter` parameter is not {minLength}-{maxLength} format', 'parameter' => $parameter]);
        }
    }

    /**
     * @param string  $field
     * @param string  $value
     * @param integer $parameter
     *
     * @return string|null
     */
    protected function _validate_minLength($field, $value, $parameter)
    {
        return mb_strlen($value) >= $parameter ? $value : null;
    }

    /**
     * @param string $field
     * @param string $value
     * @param string $parameter
     *
     * @return string|null
     */
    protected function _validate_maxLength($field, $value, $parameter)
    {
        return mb_strlen($value) <= $parameter ? $value : null;
    }

    /**
     * @param string         $field
     * @param int|float|null $value
     * @param string         $parameter
     *
     * @return int|float|null
     */
    protected function _validate_range($field, $value, $parameter)
    {
        if (!is_int($value) && !is_float($value) && ($value = $this->_validate_int($field, $value)) === null) {
            throw new ValidateFailedException([$field => $this->createError('int', $field)]);
        }

        if (preg_match('#^(-?[\.\d]+)-(-?[\d\.]+)$#', $parameter, $match)) {
            return $value >= $match[1] && $value <= $match[2] ? $value : null;
        } else {
            throw new InvalidValueException(['range validator `:parameter` parameter is not {min}-{max} format', 'parameter' => $parameter]);
        }
    }

    /**
     * @param string $field
     * @param string $value
     * @param string $parameter
     *
     * @return string|null
     */
    protected function _validate_regex($field, $value, $parameter)
    {
        return preg_match($parameter, $value) ? $value : null;
    }

    /**
     * @param string $field
     * @param string $value
     *
     * @return string|null
     */
    protected function _validate_alpha($field, $value)
    {
        return preg_match('#^[a-zA-Z]+$#', $value) ? $value : null;
    }

    /**
     * @param string $field
     * @param string $value
     *
     * @return string|null
     */
    protected function _validate_digit($field, $value)
    {
        return preg_match('#^\d+$#', $value) ? $value : null;
    }

    /**
     * @param string $field
     * @param string $value
     *
     * @return string|null
     */
    protected function _validate_xdigit($field, $value)
    {
        return preg_match('#^[0-9a-fA-F]+$#', $value) ? $value : null;
    }

    /**
     * @param string $field
     * @param string $value
     *
     * @return string|null
     */
    protected function _validate_alnum($field, $value)
    {
        return preg_match('#^[a-zA-Z0-9]+$#', $value) ? $value : null;
    }

    /**
     * @param string $field
     * @param string $value
     *
     * @return string
     */
    public function _validate_lower($field, $value)
    {
        return strtolower($value);
    }

    /**
     * @param string $field
     * @param string $value
     *
     * @return string
     */
    public function _validate_upper($field, $value)
    {
        return strtoupper($value);
    }

    /**
     * @param string       $field
     * @param string|array $value
     *
     * @return string|array
     */
    public function _validate_trim($field, $value)
    {
        if (is_array($value)) {
            $r = [];
            foreach ($value as $v) {
                if (($v = trim($v)) !== '') {
                    $r[] = $v;
                }
            }
            return $r;
        } else {
            return trim($value);
        }
    }

    /**
     * @param string $field
     * @param string $value
     *
     * @return null|string
     */
    protected function _validate_email($field, $value)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false ? strtolower($value) : null;
    }

    /**
     * @param string $field
     * @param string $value
     *
     * @return null|string
     */
    protected function _validate_url($field, $value)
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false ? $value : null;
    }

    /**
     * @param string $field
     * @param string $value
     *
     * @return null|string
     */
    protected function _validate_ip($field, $value)
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false ? $value : null;
    }

    /**
     * @param string      $field
     * @param string      $value
     * @param null|string $parameter
     *
     * @return string|int
     */
    protected function _validate_date($field, $value, $parameter = null)
    {
        $ts = is_numeric($value) ? (int)$value : strtotime($value);
        if ($ts === false) {
            return null;
        }

        return $parameter ? date($parameter, $ts) : $value;
    }

    /**
     * @param string $field
     * @param string $value
     *
     * @return string|int
     */
    protected function _validate_timestamp($field, $value)
    {
        $ts = is_numeric($value) ? (int)$value : strtotime($value);
        return $ts === false ? null : $ts;
    }

    /**
     * @param string         $field
     * @param \ManaPHP\Model $model
     * @param null|string    $parameter
     *
     * @return string|int
     */
    protected function _validate_model_date($field, $model, $parameter)
    {
        $value = $model->$field;

        if (($ts = is_numeric($value) ? (int)$value : strtotime($value)) === false) {
            return null;
        }

        return date($parameter ?: $model->getDateFormat($field), $ts);
    }

    /**
     * @param string $field
     * @param string $value
     *
     * @return string
     */
    public function _validate_escape($field, $value)
    {
        return htmlspecialchars($value);
    }

    /**
     * @param string $field
     * @param string $value
     *
     * @return string
     */
    protected function _validate_xss($field, $value)
    {
        if ($value === '') {
            return $value;
        } else {
            return $this->htmlPurifier->purify($value);
        }
    }

    /**
     * @param string     $field
     * @param string|int $value
     * @param string     $parameter
     *
     * @return string|int
     */
    protected function _validate_in($field, $value, $parameter)
    {
        return in_array($value, preg_split('#[\s,]+#', $parameter, -1, PREG_SPLIT_NO_EMPTY), false) ? $value : null;
    }

    /**
     * @param string     $field
     * @param string|int $value
     * @param string     $parameter
     *
     * @return string|int
     */
    protected function _validate_not_in($field, $value, $parameter)
    {
        return !in_array($value, preg_split('#[\s,]+#', $parameter, -1, PREG_SPLIT_NO_EMPTY), false) ? $value : null;
    }

    /**
     * @param string       $field
     * @param string       $value
     * @param string|array $parameter
     *
     * @return null|string
     */
    protected function _validate_ext($field, $value, $parameter)
    {
        $ext = strtolower(pathinfo($value, PATHINFO_EXTENSION));
        if (is_array($parameter)) {
            return in_array($ext, $parameter, true) ? $value : null;
        } else {
            return in_array($ext, preg_split('#[\s,]+#', $parameter, -1, PREG_SPLIT_NO_EMPTY), true) ? $value : null;
        }
    }


    /**
     * @param string|int     $field
     * @param \ManaPHP\Model $model
     *
     * @return int|string|null
     */
    protected function _validate_model_unique($field, $model)
    {
        $value = $model->$field;
        return $model::exists([$field => $value]) ? null : $value;
    }

    /**
     * @param string         $field
     * @param \ManaPHP\Model $model
     * @param string         $parameter
     *
     * @return string|null
     */
    protected function _validate_model_exists($field, $model, $parameter = null)
    {
        $value = $model->$field;

        if (!$value) {
            return $value;
        }

        if ($parameter) {
            $className = $parameter;
        } elseif (preg_match('#^(.*)_id$#', $field, $match)) {
            $modelName = get_class($model);
            $className = substr($modelName, 0, strrpos($modelName, '\\') + 1) . Text::camelize($match[1]);
            if (!class_exists($className)) {
                $className = $this->alias->get('@ns.app') . '\\Models\\' . Text::camelize($match[1]);
            }
        } else {
            throw new InvalidValueException(['validate `:field` field failed: related model class name is not provided', 'field' => $field]);
        }

        if (!class_exists($className)) {
            throw new InvalidValueException(['validate `:1` field failed: related `:2` model class is not exists.', $field, $className]);
        }

        /** @var \ManaPHP\ModelInterface $className */
        return $className::exists($value) ? $value : null;
    }

    /**
     * @param string         $field
     * @param \ManaPHP\Model $model
     * @param string         $parameter
     *
     * @return int|string|null
     */
    protected function _validate_model_const($field, $model, $parameter = null)
    {
        $value = $model->$field;
        $constants = $model::constants($parameter ?: $field);
        if (isset($constants[$value])) {
            return $value;
        } else {
            return ($r = array_search($value, $constants, true)) !== false ? $r : null;
        }
    }

    /**
     * @param string $field
     * @param string $value
     *
     * @return string|null
     */
    public function _validate_account($field, $value)
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
     * @param string         $field
     * @param \ManaPHP\Model $model
     *
     * @return int|string|null
     */
    public function _validate_model_account($field, $model)
    {
        $value = $model->$field;

        if (($value = $this->_validate_account($field, $value)) === null) {
            return null;
        }

        if (($value = $this->_validate_model_unique($field, $model)) === null) {
            throw new ValidateFailedException([$field => $this->createError('unique', $field)]);
        }

        return $value;
    }

    /**
     * @param string $field
     * @param string $value
     *
     * @return string|null
     */
    protected function _validate_mobile($field, $value)
    {
        $value = trim($value);

        return ($value === '' || preg_match('#^1[3-8]\d{9}$#', $value)) ? $value : null;
    }
}