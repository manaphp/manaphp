<?php

namespace ManaPHP\Exception;

class CreateDirectoryFailedException extends RuntimeException
{
    public function __construct($dir, $previous = null)
    {
        $error = error_get_last()['message'] ?? '';
        parent::__construct(['create `%s` directory failed: %s', $dir, $error], 0, $previous);
    }
}