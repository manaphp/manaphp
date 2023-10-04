<?php
declare(strict_types=1);

namespace ManaPHP\Exception;

use Exception;

class CreateDirectoryFailedException extends RuntimeException
{
    public function __construct(string $dir, ?Exception $previous = null)
    {
        $error = error_get_last()['message'] ?? '';
        parent::__construct(['create `{1}` directory failed: {2}', $dir, $error], 0, $previous);
    }
}