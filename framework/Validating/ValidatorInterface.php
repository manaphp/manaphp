<?php
declare(strict_types=1);

namespace ManaPHP\Validating;

use ManaPHP\Model\ModelInterface;

interface ValidatorInterface
{
    public function createError(string $validate, string $field, mixed $parameter = null): string;

    public function validate(string $field, mixed $value, mixed $rules): mixed;

    public function validateModel(string $field, ModelInterface $model, mixed $rules): mixed;

    public function validateValue(string $field, mixed $value, mixed $rules): mixed;
}