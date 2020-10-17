<?php

namespace ManaPHP\Exception;

class CreateDirectoryFailedException extends RuntimeException
{
    public function __construct($dir, $previous = null)
    {
        parent::__construct(['create `:dir` directory failed: :last_error_message', 'dir' => $dir], 0, $previous);
    }
}