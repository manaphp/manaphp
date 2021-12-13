<?php
declare(strict_types=1);

namespace ManaPHP\Exception;

use ManaPHP\Exception;

class MethodNotAllowedHttpException extends Exception
{
    public function __construct(array $verbs)
    {
        parent::__construct('This URL can only handle the following request methods: ' . implode(', ', $verbs));
    }

    public function getStatusCode(): int
    {
        return 405;
    }
}