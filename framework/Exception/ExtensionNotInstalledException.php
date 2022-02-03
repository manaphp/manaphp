<?php
declare(strict_types=1);

namespace ManaPHP\Exception;

class ExtensionNotInstalledException extends RuntimeException
{
    public function __construct(string $extension = '', int $code = 0, ?\Exception $previous = null)
    {
        parent::__construct("`$extension` is not installed, or the extension is not loaded", $code, $previous);
    }
}