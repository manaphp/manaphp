<?php
declare(strict_types=1);

namespace ManaPHP\Http\Filters\Csrf;

use ManaPHP\Exception;

class AttackDetectedException extends Exception
{
    public function __construct(string $message = 'Possible CSRF attack detected')
    {
        parent::__construct($message);
    }

    public function getStatusCode(): int
    {
        return 400;
    }
}