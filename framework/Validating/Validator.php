<?php
declare(strict_types=1);
/** @noinspection PhpUnusedParameterInspection */

namespace ManaPHP\Validating;

use Closure;
use ManaPHP\Component;
use ManaPHP\Data\ModelInterface;
use ManaPHP\Exception\InvalidArgumentException;
use ManaPHP\Exception\InvalidValueException;
use ManaPHP\Exception\NotSupportedException;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Helper\Str;
use ManaPHP\Validating\Validator\ValidateFailedException;

/**
 * @property-read \ManaPHP\I18n\LocaleInterface        $locale
 * @property-read \ManaPHP\Http\RequestInterface       $request
 * @property-read \ManaPHP\Data\Model\ThoseInterface   $those
 * @property-read \ManaPHP\Html\PurifierInterface      $htmlPurifier
 * @property-read \ManaPHP\Validating\ValidatorContext $context
 */
class Validator extends Component implements ValidatorInterface
{
    protected string $dir;

    protected array $files;
    protected array $templates;

    public function __construct(string $dir = '@manaphp/Validating/Validator/Templates')
    {
        $this->dir = $dir;

        foreach (LocalFS::glob($this->dir . '/*.php') as $file) {
            $this->files[strtolower(pathinfo($file, PATHINFO_FILENAME))] = $file;
        }
    }

    protected function getTemplate(string $validate): string|callable
    {
        $locale = $this->locale->get();

        $this->templates[$locale] ??= require $this->files[$locale];

        $templates = $this->templates[$locale];
        return $templates[$validate] ?? $templates['default'];
    }

    public function validate(string $field, mixed $value, mixed $rules): mixed
    {
        if ($value instanceof ModelInterface) {
            return $this->validateModel($field, $value, $rules);
        } else {
            return $this->validateValue($field, $value, $rules);
        }
    }

    public function createError(string $validate, string $field, mixed $parameter = null): string
    {
        $template = $this->getTemplate($validate);
        $tr = [':field' => $field];

        if (is_string($template)) {
            $tr[':parameter'] = $parameter;
            return strtr($template, $tr);
        } else {
            return $template($field, $parameter);
        }
    }

    public function validateModel(string $field, ModelInterface $model, mixed $rules): mixed
    {
        $value = $model->$field;

        if ($value === '' || $value === null) {
            if (is_array($rules)) {
                //default value maybe is `NULL`
                if (array_key_exists('default', $rules)) {
                    return $model->$field = $rules['default'];
                } elseif (in_array('safe', $rules, true)) {
                    return $model->$field = '';
                }
            } elseif ($rules === 'safe') {
                return $model->$field = '';
            }

            throw new ValidateFailedException([$field => $this->createError('required', $field)]);
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
                        $value = $r;
                    }
                    continue;
                } elseif (str_contains($v, '-')) {
                    $validate = in_array($field, $model->intFields(), true) ? 'range' : 'length';
                    $parameter = $v;
                } else {
                    $validate = $v;
                    $parameter = null;
                }
            } else {
                $validate = $k;
                $parameter = $v;
            }

            if (method_exists($this, $method = 'validate_model_' . $validate)) {
                if ($parameter === null) {
                    $value = $this->$method($field, $model);
                } else {
                    $value = $this->$method($field, $model, $parameter);
                }
            } elseif (method_exists($this, $method = 'validate_' . $validate)) {
                if ($parameter === null) {
                    $value = $this->$method($field, $value);
                } else {
                    $value = $this->$method($field, $value, $parameter);
                }
            } else {
                throw new NotSupportedException(['unsupported `:validate` validate method', 'validate' => $validate]);
            }

            if ($value === null) {
                throw new ValidateFailedException([$field => $this->createError($validate, $field, $parameter)]);
            }
        }

        return $model->$field = $value;
    }

    public function validateValue(string $field, mixed $value, mixed $rules): mixed
    {
        if ($value === '' || $value === null) {
            if (is_array($rules)) {
                //default value maybe is `NULL`
                if (array_key_exists('default', $rules)) {
                    return $rules['default'];
                } elseif (in_array('safe', $rules, true)) {
                    return '';
                }
            } elseif ($rules === 'safe') {
                return '';
            }

            throw new ValidateFailedException([$field => $this->createError('required', $field)]);
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
                        $value = $r;
                    }
                    continue;
                } elseif (str_contains($v, '-')) {
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

            if (method_exists($this, $method = 'validate_' . $validate)) {
                if ($parameter === null) {
                    $value = $this->$method($field, $value);
                } else {
                    $value = $this->$method($field, $value, $parameter);
                }
            } else {
                throw new NotSupportedException(['unsupported `:validate` validate method', 'validate' => $validate]);
            }

            if ($value === null) {
                throw new ValidateFailedException([$field => $this->createError($validate, $field, $parameter)]);
            }
        }

        return $value;
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function validate_required(string $field, ?string $value): ?string
    {
        return $value !== null && $value !== '' ? $value : null;
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function validate_default(string $field, mixed $value): mixed
    {
        return $value;
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function validate_bool(string $field, mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (str_contains(',1,true,on,yes,', ",$value,")) {
            return true;
        } elseif (str_contains(',0,false,off,no,', ",$value,")) {
            return false;
        } else {
            return null;
        }
    }

    protected function validate_boolean(string $field, mixed $value): ?bool
    {
        return $this->validate_bool($field, $value);
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function validate_int(string $field, mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        return preg_match('#^[+\-]?\d+$#', $value) ? (int)$value : null;
    }

    protected function validate_integer(string $field, mixed $value): ?int
    {
        return $this->validate_int($field, $value);
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function validate_float(string $field, mixed $value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        if (filter_var($value, FILTER_VALIDATE_FLOAT) !== false
            && preg_match('#^[+\-]?[\d.]+$#', $value) === 1
        ) {
            return (float)$value;
        } else {
            return null;
        }
    }

    protected function validate_double(string $field, mixed $value): ?float
    {
        return $this->validate_float($field, $value);
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function validate_string(string $field, mixed $value): string
    {
        return (string)$value;
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function validate_array(string $field, mixed $value): array
    {
        return is_string($value) ? preg_split('#,#', $value, -1, PREG_SPLIT_NO_EMPTY) : (array)$value;
    }

    protected function normalizeNumber(string $field, mixed $value, mixed $parameter): int|float
    {
        if (!is_int($value) && !is_float($value)) {
            if (str_contains($parameter, '.')) {
                if (($value = $this->validate_float($field, $value)) === null) {
                    throw new ValidateFailedException([$field => $this->createError('float', $field)]);
                }
            } else {
                if (($value = $this->validate_int($field, $value)) === null) {
                    throw new ValidateFailedException([$field => $this->createError('int', $field)]);
                }
            }
        }

        return $value;
    }

    protected function validate_min(string $field, mixed $value, mixed $parameter): int|null|float
    {
        $number = $this->normalizeNumber($field, $value, $parameter);

        return $number < $parameter ? null : $number;
    }

    protected function validate_max(string $field, mixed $value, mixed $parameter): int|null|float
    {
        $number = $this->normalizeNumber($field, $value, $parameter);

        return $number > $parameter ? null : $number;
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function validate_length(string $field, string $value, string $parameter): ?string
    {
        $len = mb_strlen($value);
        if (preg_match('#^(\d+)-(\d+)$#', $parameter, $match)) {
            return $len >= $match[1] && $len <= $match[2] ? $value : null;
        } elseif (is_numeric($parameter)) {
            return $len === (int)$parameter ? $value : null;
        } else {
            throw new InvalidValueException(['length validator `%s` parameter is not {min}-{max} format', $parameter]);
        }
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function validate_minLength(string $field, string $value, int $parameter): ?string
    {
        return mb_strlen($value) >= $parameter ? $value : null;
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function validate_maxLength(string $field, string $value, string $parameter): ?string
    {
        return mb_strlen($value) <= $parameter ? $value : null;
    }

    protected function validate_range(string $field, mixed $value, string $parameter): int|null|float
    {
        if (!preg_match('#^(-?[.\d]+)-(-?[\d.]+)$#', $parameter, $match)) {
            throw new InvalidValueException(['range validator `%s` parameter is not {min}-{max} format', $parameter]);
        }

        $number = $this->normalizeNumber($field, $value, $parameter);

        return $number >= $match[1] && $number <= $match[2] ? $number : null;
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function validate_regex(string $field, string $value, string $parameter): ?string
    {
        return preg_match($parameter, $value) ? $value : null;
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function validate_alpha(string $field, string $value): ?string
    {
        return preg_match('#^[a-zA-Z]+$#', $value) ? $value : null;
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function validate_digit(string $field, string $value): ?string
    {
        return preg_match('#^\d+$#', $value) ? $value : null;
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function validate_xdigit(string $field, string $value): ?string
    {
        return preg_match('#^[0-9a-fA-F]+$#', $value) ? $value : null;
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function validate_alnum(string $field, string $value): ?string
    {
        return preg_match('#^[a-zA-Z0-9]+$#', $value) ? $value : null;
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function validate_lower(string $field, string $value): string
    {
        return strtolower($value);
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function validate_upper(string $field, string $value): string
    {
        return strtoupper($value);
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function validate_trim(string $field, mixed $value): string|array
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

    /** @noinspection PhpUnusedParameterInspection */
    protected function validate_email(string $field, string $value): ?string
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false ? strtolower($value) : null;
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function validate_url(string $field, string $value): ?string
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false ? $value : null;
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function validate_ip(string $field, string $value): ?string
    {
        return filter_var($value, FILTER_VALIDATE_IP) !== false ? $value : null;
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function validate_date(string $field, string $value, ?string $parameter = null): ?string
    {
        $ts = is_numeric($value) ? (int)$value : strtotime($value);
        if ($ts === false) {
            return null;
        }

        return $parameter ? date($parameter, $ts) : $value;
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function validate_timestamp(string $field, mixed $value): ?int
    {
        $ts = is_numeric($value) ? (int)$value : strtotime($value);
        return $ts === false ? null : $ts;
    }

    protected function validate_model_date(string $field, ModelInterface $model, ?string $parameter): ?string
    {
        $value = $model->$field;

        if (($ts = is_numeric($value) ? (int)$value : strtotime($value)) === false) {
            return null;
        }

        return date($parameter ?: $model->dateFormat($field), $ts);
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function validate_escape(string $field, string $value): string
    {
        return htmlspecialchars($value);
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function validate_xss(string $field, string $value): string
    {
        if ($value === '') {
            return $value;
        } else {
            return $this->htmlPurifier->purify($value);
        }
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function validate_uuid(string $field, string $value): ?string
    {
        return preg_match('#^[0-9a-f]{8}(-[0-9a-f]{4}){3}-[0-9a-f]{12}$#i', $value) === 1 ? $value : null;
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function validate_in(string $field, mixed $value, string $parameter): mixed
    {
        return in_array($value, preg_split('#[\s,]+#', $parameter, -1, PREG_SPLIT_NO_EMPTY), false) ? $value : null;
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function validate_not_in(string $field, mixed $value, string $parameter): mixed
    {
        return !in_array($value, preg_split('#[\s,]+#', $parameter, -1, PREG_SPLIT_NO_EMPTY), false) ? $value : null;
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function validate_ext(string $field, string $value, mixed $parameter): ?string
    {
        $ext = strtolower(pathinfo($value, PATHINFO_EXTENSION));
        if (is_array($parameter)) {
            return in_array($ext, $parameter, true) ? $value : null;
        } else {
            return in_array($ext, preg_split('#[\s,]+#', $parameter, -1, PREG_SPLIT_NO_EMPTY), true) ? $value : null;
        }
    }

    protected function validate_model_unique(string $field, ModelInterface $model, mixed $parameters = null): mixed
    {
        $value = $model->$field;

        $filters = [$field => $value];

        if (is_string($parameters)) {
            foreach (explode(',', $parameters) as $parameter) {
                if (($parameter = trim($parameter)) !== '') {
                    $filters[$parameter] = $model->$parameter;
                }
            }
        } elseif (is_array($parameters)) {
            foreach ($parameters as $k => $v) {
                if (is_int($k)) {
                    $filters[$v] = $model->$v;
                } else {
                    $filters[$k] = $v;
                }
            }
        }

        return $model::exists($filters) ? null : $value;
    }

    protected function validate_model_exists(string $field, ModelInterface $model, ?string $parameter = null): mixed
    {
        $value = $model->$field;

        if (!$value) {
            return $value;
        }

        if ($parameter) {
            $className = $parameter;
        } elseif ($field === 'parent_id') {
            $className = $model::class;
        } elseif (preg_match('#^(.*)_id$#', $field, $match)) {
            $modelName = $model::class;
            $className = substr($modelName, 0, strrpos($modelName, '\\') + 1) . Str::pascalize($match[1]);
            if (!class_exists($className)) {
                $className = 'App\\Models\\' . Str::pascalize($match[1]);
            }
        } else {
            throw new InvalidValueException(['validate `%s` failed: related model class is not provided', $field]);
        }

        if (!class_exists($className)) {
            throw new InvalidValueException(['validate `:1` failed: `:2` class is not exists.', $field, $className]);
        }

        /** @var \ManaPHP\Data\ModelInterface $className */
        $class = (string)$className;
        return $className::exists([$this->those->get($class)->primaryKey() => $value]) ? $value : null;
    }

    protected function validate_model_level(string $field, ModelInterface $model, ?string $parameter = null): mixed
    {
        $value = $model->$field;

        if (!$value) {
            return 0;
        } else {
            return $this->validate_model_exists($field, $model, $parameter);
        }
    }

    protected function validate_model_const(string $field, ModelInterface $model, ?string $parameter = null): mixed
    {
        $value = $model->$field;
        $constants = $model::constants($parameter ?: $field);
        if (isset($constants[$value])) {
            return $value;
        } else {
            return ($r = array_search($value, $constants, true)) !== false ? $r : null;
        }
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function validate_account(string $field, string $value): ?string
    {
        $value = strtolower($value);

        if (!preg_match('#^[a-z][a-z0-9_]{2,}$#', $value)) {
            return null;
        }

        if (str_contains($value, '__')) {
            return null;
        }

        return $value;
    }

    protected function validate_model_account(string $field, ModelInterface $model): ?string
    {
        $value = $model->$field;

        if ($this->validate_account($field, $value) === null) {
            return null;
        }

        if (($value = $this->validate_model_unique($field, $model)) === null) {
            throw new ValidateFailedException([$field => $this->createError('unique', $field)]);
        }

        return $value;
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function validate_mobile(string $field, string $value): ?string
    {
        $value = trim($value);

        return ($value === '' || preg_match('#^1[3-8]\d{9}$#', $value)) ? $value : null;
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function validate_safe(string $field, mixed $value): mixed
    {
        return $value;
    }

    protected function validate_model_readonly(string $field, ModelInterface $model): mixed
    {
        $value = $model->$field;

        $snap = $model->getSnapshotData();
        if (isset($snap[$field]) && $snap[$field] !== $value) {
            return null;
        } else {
            return $value;
        }
    }
}