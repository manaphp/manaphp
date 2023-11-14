<?php
declare(strict_types=1);

namespace ManaPHP\Validating;

class Validation
{
    protected array $errors = [];
    public string $field;
    public mixed $value;

    public function __construct(public ValidatorInterface $validator, public array|object $source)
    {

    }

    public function validate(ConstraintInterface $constraint): bool
    {
        if (isset($this->errors[$this->field])) {
            return false;
        }

        if (!$constraint->validate($this)) {
            $this->addError($constraint->getMessage(), (array)$constraint);
        }

        return !isset($this->errors[$this->field]);
    }

    public function addError(string $message, array $placeholders = []): void
    {
        $placeholders['field'] = $this->field;
        $this->errors[$this->field] = $this->validator->formatMessage($message, $placeholders);
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]);
    }
}