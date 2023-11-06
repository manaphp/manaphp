<?php
declare(strict_types=1);

namespace ManaPHP\Validating;

interface ValidatorInterface
{
    public function validate(array $source, array $rules): array;

    public function beginValidate(array|object $source): Validation;

    public function endValidate(Validation $validation): void;

    public function formatMessage(string $message, array $placeholders = []): string;
}