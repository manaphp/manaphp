<?php
declare(strict_types=1);

namespace ManaPHP\Data\Query;

use ManaPHP\Exception;

class NotFoundException extends Exception
{
    public function getStatusCode(): int
    {
        return 404;
    }

    public function getJson(): array
    {
        return ['code' => 404, 'message' => 'record is not exists'];
    }
}