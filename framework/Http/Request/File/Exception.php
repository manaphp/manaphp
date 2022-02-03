<?php
declare(strict_types=1);

namespace ManaPHP\Http\Request\File;

class Exception extends \ManaPHP\Http\Request\Exception
{
    public function getStatusCode(): int
    {
        return 400;
    }
}