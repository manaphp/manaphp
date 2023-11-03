<?php
declare(strict_types=1);

namespace ManaPHP\Validating\Validator;

use ManaPHP\Exception;

class ValidateFailedException extends Exception
{
    protected array $errors;

    public function __construct(array $errors, int $code = 0, ?\Exception $previous = null)
    {
        $this->errors = $errors;
        $this->json = ['code' => -1, 'msg' => json_stringify($errors, JSON_PRETTY_PRINT)];

        parent::__construct(json_stringify($errors), $code, $previous);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getStatusCode(): int
    {
        return 400;
    }
}