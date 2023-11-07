<?php
declare(strict_types=1);

namespace ManaPHP\Validating;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Di\Lazy;
use ManaPHP\Helper\LocalFS;
use ManaPHP\I18n\LocaleInterface;
use ManaPHP\Validating\Validator\ValidateFailedException;

class Validator implements ValidatorInterface
{
    #[Autowired] protected LocaleInterface|Lazy $locale;

    #[Autowired] protected string $dir = '@manaphp/Validating/Validator/Templates';

    protected array $files;
    protected array $templates;

    public function __construct()
    {
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

    public function validateValues(array $source, array $rules): array
    {
        $validation = $this->beginValidate($source);

        $values = [];
        foreach ($rules as $attribute => $attribute_rules) {
            $validation->field = $attribute;
            $validation->value = $source[$attribute] ?? null;

            foreach (\is_array($attribute_rules) ? $attribute_rules : [$attribute_rules] as $rule) {
                if (!$validation->validate($rule)) {
                    break;
                }
            }

            if (!$validation->hasError($attribute)) {
                $values[$attribute] = $validation->value;
            }
        }

        $this->endValidate($validation);

        return $values;
    }

    public function validateValue(string $field, mixed $value, array $rules): mixed
    {
        return $this->validateValues([$field => $value], $rules)[$field] ?? null;
    }

    public function beginValidate(array|object $source): Validation
    {
        return new Validation($this, $source);
    }

    public function endValidate(Validation $validation): void
    {
        if (($errors = $validation->getErrors()) !== []) {
            throw new ValidateFailedException($errors);
        }
    }

    public function formatMessage(string $message, array $placeholders = []): string
    {
        return \preg_replace_callback('#:(\w+)#', static function ($match) use ($placeholders) {
            $name = $match[1];
            return $placeholders[$name] ?? $match[0];
        }, $this->getTemplate($message));
    }
}