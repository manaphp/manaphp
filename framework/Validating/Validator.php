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

    public function validateValues(array $source, array $constraints): array
    {
        $validation = $this->beginValidate($source);

        $values = [];
        foreach ($constraints as $field => $field_constraints) {
            $validation->field = $field;
            $validation->value = $source[$field] ?? null;

            foreach (\is_array($field_constraints) ? $field_constraints : [$field_constraints] as $constraint) {
                if (!$validation->validate($constraint)) {
                    break;
                }
            }

            if (!$validation->hasError($field)) {
                $values[$field] = $validation->value;
            }
        }

        $this->endValidate($validation);

        return $values;
    }

    public function validateValue(string $field, mixed $value, array $constraints): mixed
    {
        return $this->validateValues([$field => $value], [$field => $constraints])[$field] ?? null;
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