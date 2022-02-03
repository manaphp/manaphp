<?php
declare(strict_types=1);

namespace ManaPHP\Exception;

use ManaPHP\Exception;

class NotFoundException extends Exception
{
    public function getStatusCode(): int
    {
        return 404;
    }
}